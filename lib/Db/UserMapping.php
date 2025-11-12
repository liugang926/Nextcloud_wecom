<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getWecomUserId()
 * @method void setWecomUserId(string $wecomUserId)
 * @method string getNextcloudUid()
 * @method void setNextcloudUid(string $nextcloudUid)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 * @method string getEmail()
 * @method void setEmail(string $email)
 * @method string getMobile()
 * @method void setMobile(string $mobile)
 * @method string getDepartmentIds()
 * @method void setDepartmentIds(string $departmentIds)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 * @method int getLastLoginAt()
 * @method void setLastLoginAt(int $lastLoginAt)
 */
class UserMapping extends Entity {
	protected string $wecomUserId = '';
	protected string $nextcloudUid = '';
	protected string $displayName = '';
	protected string $email = '';
	protected string $mobile = '';
	protected string $departmentIds = '';
	protected int $createdAt = 0;
	protected int $updatedAt = 0;
	protected int $lastLoginAt = 0;

	public function __construct() {
		$this->addType('wecomUserId', 'string');
		$this->addType('nextcloudUid', 'string');
		$this->addType('displayName', 'string');
		$this->addType('email', 'string');
		$this->addType('mobile', 'string');
		$this->addType('departmentIds', 'string');
		$this->addType('createdAt', 'integer');
		$this->addType('updatedAt', 'integer');
		$this->addType('lastLoginAt', 'integer');
	}
}
