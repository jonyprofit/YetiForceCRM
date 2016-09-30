<?php namespace App;

/**
 * Privilege Util basic class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class PrivilegeUtil
{

	/** Function to get parent record owner
	 * @param $tabid -- tabid :: Type integer
	 * @param $parModId -- parent module id :: Type integer
	 * @param $recordId -- record id :: Type integer
	 * @returns $parentRecOwner -- parentRecOwner:: Type integer
	 */
	public static function getParentRecordOwner($tabid, $parModId, $recordId)
	{
		\App\log::trace("Entering getParentRecordOwner($tabid,$parModId,$recordId) method ...");
		$parentRecOwner = [];
		$parentTabName = \vtlib\Functions::getModuleName($parModId);
		$relTabName = \vtlib\Functions::getModuleName($tabid);
		$fn_name = 'get' . $relTabName . 'Related' . $parentTabName;
		$entId = self::$fn_name($recordId);
		if ($entId != '') {
			$recordMetaData = \vtlib\Functions::getCRMRecordMetadata($entId);
			if ($recordMetaData) {
				$ownerId = $recordMetaData['smownerid'];
				$type = \includes\fields\Owner::getType($ownerId);
				$parentRecOwner[$type] = $ownerId;
			}
		}
		\App\log::trace('Exiting getParentRecordOwner method ...');
		return $parentRecOwner;
	}

	/** Function to get email related accounts
	 * @param $recordId -- record id :: Type integer
	 * @returns $accountid -- accountid:: Type integer
	 */
	private static function getEmailsRelatedAccounts($recordId)
	{
		\App\log::trace("Entering getEmailsRelatedAccounts($recordId) method ...");
		$adb = \PearDatabase::getInstance();
		$query = "select vtiger_seactivityrel.crmid from vtiger_seactivityrel inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_seactivityrel.crmid where vtiger_crmentity.setype='Accounts' and activityid=?";
		$result = $adb->pquery($query, array($recordId));
		$accountid = $adb->getSingleValue($result);
		\App\log::trace('Exiting getEmailsRelatedAccounts method ...');
		return $accountid;
	}

	/** Function to get email related Leads
	 * @param $recordId -- record id :: Type integer
	 * @returns $leadid -- leadid:: Type integer
	 */
	private static function getEmailsRelatedLeads($recordId)
	{
		\App\log::trace("Entering getEmailsRelatedLeads($recordId) method ...");
		$adb = \PearDatabase::getInstance();
		$query = "select vtiger_seactivityrel.crmid from vtiger_seactivityrel inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_seactivityrel.crmid where vtiger_crmentity.setype='Leads' and activityid=?";
		$result = $adb->pquery($query, array($recordId));
		$leadid = $adb->getSingleValue($result);
		\App\log::trace('Exiting getEmailsRelatedLeads method ...');
		return $leadid;
	}

	/** Function to get HelpDesk related Accounts
	 * @param $recordId -- record id :: Type integer
	 * @returns $accountid -- accountid:: Type integer
	 */
	private static function getHelpDeskRelatedAccounts($recordId)
	{
		\App\log::trace("Entering getHelpDeskRelatedAccounts($recordId) method ...");
		$adb = \PearDatabase::getInstance();
		$query = "select parent_id from vtiger_troubletickets inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_troubletickets.parent_id where ticketid=? and vtiger_crmentity.setype='Accounts'";
		$result = $adb->pquery($query, array($recordId));
		$accountid = $adb->getSingleValue($result);
		\App\log::trace('Exiting getHelpDeskRelatedAccounts method ...');
		return $accountid;
	}

	protected static $datashareRelatedCache = false;

	public static function getDatashareRelatedModules()
	{
		if (self::$datashareRelatedCache === false) {
			$relModSharArr = [];
			$adb = \PearDatabase::getInstance();
			$result = $adb->query('select * from vtiger_datashare_relatedmodules');
			while ($row = $adb->getRow($result)) {
				$relTabId = $row['relatedto_tabid'];
				if (is_array($relModSharArr[$relTabId])) {
					$temArr = $relModSharArr[$relTabId];
					$temArr[] = $row['tabid'];
				} else {
					$temArr = [];
					$temArr[] = $row['tabid'];
				}
				$relModSharArr[$relTabId] = $temArr;
			}
			self::$datashareRelatedCache = $relModSharArr;
		}
		return self::$datashareRelatedCache;
	}

	protected static $defaultSharingActionCache = false;

	public static function getAllDefaultSharingAction()
	{
		if (self::$defaultSharingActionCache === false) {
			\App\log::trace('Entering getAllDefaultSharingAction() method ...');
			$adb = \PearDatabase::getInstance();
			$copy = [];
			//retreiving the standard permissions
			$result = $adb->query('select * from vtiger_def_org_share');
			while ($row = $adb->getRow($result)) {
				$copy[$row['tabid']] = $row['permission'];
			}
			self::$defaultSharingActionCache = $copy;
			\App\log::trace('Exiting getAllDefaultSharingAction method ...');
		}
		return self::$defaultSharingActionCache;
	}

	protected static $roleUserCache = [];

	/** Function to get the vtiger_role related user ids
	 * @param $roleid -- RoleId :: Type varchar
	 * @returns $roleUserIds-- Role Related User Array in the following format:
	 *       $roleUserIds=Array($userId1,$userId2,........,$userIdn);
	 */
	public static function getRoleUserIds($roleId)
	{
		if (!isset(self::$roleUserCache[$roleId])) {
			\App\log::trace("Entering getRoleUserIds($roleId) method ...");
			$adb = \PearDatabase::getInstance();
			$query = 'select vtiger_user2role.userid,vtiger_users.user_name from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid where roleid=?';
			$result = $adb->pquery($query, array($roleId));
			$roleRelatedUsers = [];
			while (($userid = $adb->getSingleValue($result)) !== false) {
				$roleRelatedUsers[] = $userid;
			}
			self::$roleUserCache[$roleId] = $roleRelatedUsers;
			\App\log::trace('Exiting getRoleUserIds method ...');
		}
		return self::$roleUserCache[$roleId];
	}
}