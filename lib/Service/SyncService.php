<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Service;

use OCA\OAuthWeCom\Db\UserMapping;
use OCA\OAuthWeCom\Db\UserMappingMapper;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * 用户同步服务
 * 从企业微信同步组织架构和用户信息到NextCloud
 */
class SyncService {
	public function __construct(
		private ConfigService $configService,
		private WeComApiService $weComApiService,
		private UserMappingMapper $userMappingMapper,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * 执行完整同步
	 */
	public function fullSync(): array {
		$result = [
			'success' => false,
			'total_users' => 0,
			'created_users' => 0,
			'updated_users' => 0,
			'total_departments' => 0,
			'created_groups' => 0,
			'errors' => [],
			'message' => '',
		];

		try {
			// 检查配置是否有效
			if (!$this->configService->isConfigured()) {
				throw new \Exception('WeCom configuration is invalid');
			}

			// 同步部门
			$departmentResult = $this->syncDepartments();
			$result['total_departments'] = $departmentResult['total'];
			$result['created_groups'] = $departmentResult['created'];
			$result['errors'] = array_merge($result['errors'], $departmentResult['errors']);

			// 同步用户
			$userResult = $this->syncUsers();
			$result['total_users'] = $userResult['total'];
			$result['created_users'] = $userResult['created'];
			$result['updated_users'] = $userResult['updated'];
			$result['errors'] = array_merge($result['errors'], $userResult['errors']);

			$result['success'] = true;
			$result['message'] = sprintf(
				'Synchronized %d users (%d created, %d updated) and %d departments (%d groups created)',
				$result['total_users'],
				$result['created_users'],
				$result['updated_users'],
				$result['total_departments'],
				$result['created_groups']
			);

			$this->logger->info('Full sync completed', $result);
		} catch (\Exception $e) {
			$result['errors'][] = $e->getMessage();
			$result['message'] = 'Sync failed: ' . $e->getMessage();
			$this->logger->error('Full sync failed', ['exception' => $e]);
		}

		return $result;
	}

	/**
	 * 同步部门
	 */
	private function syncDepartments(): array {
		$result = [
			'total' => 0,
			'created' => 0,
			'errors' => [],
		];

		try {
			// 获取所有部门
			$departments = $this->weComApiService->getDepartmentList();
			$result['total'] = count($departments);

			foreach ($departments as $department) {
				try {
					$this->syncDepartment($department);
					$result['created']++;
				} catch (\Exception $e) {
					$result['errors'][] = sprintf(
						'Failed to sync department %s: %s',
						$department['name'] ?? 'unknown',
						$e->getMessage()
					);
					$this->logger->warning('Failed to sync department', [
						'department' => $department,
						'exception' => $e,
					]);
				}
			}
		} catch (\Exception $e) {
			$result['errors'][] = 'Failed to get departments: ' . $e->getMessage();
			$this->logger->error('Failed to get departments', ['exception' => $e]);
		}

		return $result;
	}

	/**
	 * 同步单个部门
	 */
	private function syncDepartment(array $department): void {
		$departmentName = $department['name'] ?? null;
		
		if (empty($departmentName)) {
			return;
		}

		// 创建或更新用户组
		$group = $this->groupManager->get($departmentName);
		
		if ($group === null) {
			// 创建新组
			$this->groupManager->createGroup($departmentName);
			$this->logger->info('Created group for department', ['name' => $departmentName]);
		}
	}

	/**
	 * 同步用户
	 */
	private function syncUsers(): array {
		$result = [
			'total' => 0,
			'created' => 0,
			'updated' => 0,
			'errors' => [],
		];

		try {
			// 获取需要同步的部门ID列表
			$departmentIds = $this->getSyncDepartmentIds();

			foreach ($departmentIds as $departmentId) {
				try {
					// 获取部门用户详情
					$users = $this->weComApiService->getDepartmentUsersDetail((int)$departmentId);
					
					foreach ($users as $wecomUser) {
						try {
							$syncResult = $this->syncUser($wecomUser);
							$result['total']++;
							
							if ($syncResult === 'created') {
								$result['created']++;
							} elseif ($syncResult === 'updated') {
								$result['updated']++;
							}
						} catch (\Exception $e) {
							$result['errors'][] = sprintf(
								'Failed to sync user %s: %s',
								$wecomUser['userid'] ?? 'unknown',
								$e->getMessage()
							);
							$this->logger->warning('Failed to sync user', [
								'user' => $wecomUser,
								'exception' => $e,
							]);
						}
					}
				} catch (\Exception $e) {
					$result['errors'][] = sprintf(
						'Failed to get users for department %d: %s',
						$departmentId,
						$e->getMessage()
					);
					$this->logger->warning('Failed to get department users', [
						'departmentId' => $departmentId,
						'exception' => $e,
					]);
				}
			}
		} catch (\Exception $e) {
			$result['errors'][] = 'Failed to sync users: ' . $e->getMessage();
			$this->logger->error('Failed to sync users', ['exception' => $e]);
		}

		return $result;
	}

	/**
	 * 同步单个用户
	 */
	private function syncUser(array $wecomUser): string {
		$wecomUserId = $wecomUser['userid'] ?? null;
		
		if (empty($wecomUserId)) {
			throw new \Exception('WeCom user ID is empty');
		}

		// 查找是否已有映射
		try {
			$mapping = $this->userMappingMapper->findByWecomUserId($wecomUserId);
			$user = $this->userManager->get($mapping->getNextcloudUid());
			
			if ($user !== null) {
				// 更新现有用户
				$this->updateUser($user, $wecomUser);
				$this->updateUserMapping($mapping, $wecomUser);
				return 'updated';
			}
		} catch (\Exception $e) {
			// 映射不存在，继续创建
		}

		// 创建新用户
		$user = $this->createUser($wecomUser);
		
		if ($user !== null) {
			$this->createUserMapping($wecomUserId, $user->getUID(), $wecomUser);
			return 'created';
		}

		throw new \Exception('Failed to create user');
	}

	/**
	 * 创建用户
	 */
	private function createUser(array $wecomUser): ?IUser {
		$username = $wecomUser['userid'];
		
		// 如果用户名已存在，添加后缀
		if ($this->userManager->get($username) !== null) {
			$username = $username . '_' . time();
		}

		// 生成随机密码
		$password = bin2hex(random_bytes(32));
		
		// 创建用户
		$user = $this->userManager->createUser($username, $password);
		
		if ($user === null) {
			return null;
		}

		// 更新用户信息
		$this->updateUser($user, $wecomUser);

		return $user;
	}

	/**
	 * 更新用户信息
	 */
	private function updateUser(IUser $user, array $wecomUser): void {
		// 更新显示名称
		if (!empty($wecomUser['name'])) {
			$user->setDisplayName($wecomUser['name']);
		}

		// 更新邮箱
		if (!empty($wecomUser['email'])) {
			$user->setEMailAddress($wecomUser['email']);
		}

		// 更新用户组（根据部门）
		if (!empty($wecomUser['department'])) {
			$this->updateUserGroups($user, $wecomUser['department']);
		}
	}

	/**
	 * 更新用户组
	 */
	private function updateUserGroups(IUser $user, array $departmentIds): void {
		try {
			// 获取所有部门信息
			$departments = $this->weComApiService->getDepartmentList();
			$departmentMap = [];
			
			foreach ($departments as $dept) {
				$departmentMap[$dept['id']] = $dept['name'];
			}

			// 为每个部门添加用户到对应的组
			foreach ($departmentIds as $deptId) {
				if (isset($departmentMap[$deptId])) {
					$groupName = $departmentMap[$deptId];
					$group = $this->groupManager->get($groupName);
					
					if ($group !== null && !$group->inGroup($user)) {
						$group->addUser($user);
					}
				}
			}
		} catch (\Exception $e) {
			$this->logger->warning('Failed to update user groups', ['exception' => $e]);
		}
	}

	/**
	 * 创建用户映射
	 */
	private function createUserMapping(string $wecomUserId, string $nextcloudUid, array $wecomUser): void {
		$mapping = new UserMapping();
		$mapping->setWecomUserId($wecomUserId);
		$mapping->setNextcloudUid($nextcloudUid);
		$mapping->setDisplayName($wecomUser['name'] ?? '');
		$mapping->setEmail($wecomUser['email'] ?? '');
		$mapping->setMobile($wecomUser['mobile'] ?? '');
		$mapping->setDepartmentIds(json_encode($wecomUser['department'] ?? []));
		$mapping->setCreatedAt(time());
		$mapping->setUpdatedAt(time());
		$mapping->setLastLoginAt(0);
		
		$this->userMappingMapper->insert($mapping);
	}

	/**
	 * 更新用户映射
	 */
	private function updateUserMapping(UserMapping $mapping, array $wecomUser): void {
		$mapping->setDisplayName($wecomUser['name'] ?? '');
		$mapping->setEmail($wecomUser['email'] ?? '');
		$mapping->setMobile($wecomUser['mobile'] ?? '');
		$mapping->setDepartmentIds(json_encode($wecomUser['department'] ?? []));
		$mapping->setUpdatedAt(time());
		
		$this->userMappingMapper->update($mapping);
	}

	/**
	 * 获取需要同步的部门ID列表
	 */
	private function getSyncDepartmentIds(): array {
		$syncDepartments = $this->configService->getSyncDepartments();
		
		if (empty($syncDepartments)) {
			return ['1']; // 默认同步根部门
		}

		return $syncDepartments;
	}
}

