<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getWecomUserId()
 * @method void setWecomUserId(string $wecomUserId)
 * @method string getNextcloudUid()
 * @method void setNextcloudUid(string $nextcloudUid)
 * @method string getAction()
 * @method void setAction(string $action)
 * @method string getStatus()
 * @method void setStatus(string $status)
 * @method string getIpAddress()
 * @method void setIpAddress(string $ipAddress)
 * @method string getUserAgent()
 * @method void setUserAgent(string $userAgent)
 * @method string getMessage()
 * @method void setMessage(string $message)
 * @method string getDetails()
 * @method void setDetails(string $details)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class AuditLog extends Entity {
	protected string $wecomUserId = '';
	protected string $nextcloudUid = '';
	protected string $action = '';
	protected string $status = '';
	protected string $ipAddress = '';
	protected string $userAgent = '';
	protected string $message = '';
	protected string $details = '';
	protected int $createdAt = 0;

	public function __construct() {
		$this->addType('wecomUserId', 'string');
		$this->addType('nextcloudUid', 'string');
		$this->addType('action', 'string');
		$this->addType('status', 'string');
		$this->addType('ipAddress', 'string');
		$this->addType('userAgent', 'string');
		$this->addType('message', 'string');
		$this->addType('details', 'string');
		$this->addType('createdAt', 'integer');
	}
}
