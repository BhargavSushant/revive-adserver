<?php

/*
+---------------------------------------------------------------------------+
| Openads v${RELEASE_MAJOR_MINOR}                                                              |
| ============                                                              |
|                                                                           |
| Copyright (c) 2003-2007 Openads Limited                                   |
| For contact details, see: http://www.openads.org/                         |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
$Id$
*/

require_once MAX_PATH . '/lib/OA.php';
require_once MAX_PATH . '/lib/max/Plugin/Common.php';
require_once MAX_PATH . '/lib/max/Plugin/Translation.php';

/**
 * Plugins_statisticsFieldsDelivery_statisticsFieldsDelivery is an abstract
 * class for every delivery statistics fields plugin.
 *
 * @abstract
 * @package    OpenadsPlugin
 * @subpackage StatisticsFields
 * @author     Matteo Beccati <matteo@beccati.com>
 */
class Plugins_statisticsFieldsDelivery_statisticsFieldsDelivery extends MAX_Plugin_Common
{

    /**
     * An array of the fields that the statistics plugin provides support for.
     *
     * The array needs to have the following format:
     *
     * array(
     *      'field' => array(
     *                     'name'   => 'String of the full name of the column',
     *                     'short'  => 'String of the abbrev. name of the column',
     *                     'pref'   => 'Optional string name of the preference (1)',
     *                     'ctrl'   => 'Optional string of class name (2)',
     *                     'format' => 'Format string name (3)',
     *                     'link'   => 'Optional partial URI string (4)'
     *                 )
     *         .
     *         .
     *         .
     * )
     *
     * where "field" is the name of the field in the data array generated by
     * the statistics class using the plugin.
     *
     * (1) If the preference is named and set, can control the name of the column
     *     that is displayed, based on user preference (ie. overwriting the
     *     "name" and "short" values). Also controls if the column is visible
     *     or not, based on the same user preference name.
     *
     * (2) If the controlling statistics class is of the class named, or a sub-class
     *     of this class, then the column will be used in the display - otherwise
     *     this column will not be used.
     *
     * (3) One of: "id", "default", "percent", "currency".
     *
     * (4) If set, the value of the data, when displayed, will be a link to the
     *     page specified. For example, the pending conversions data item has
     *     the "link" value of "stats.php?entity=conversions&". Additional
     *     required page parameters will be set by the calling
     *     OA_Admin_Statistics_Common or child class.
     *
     * @var array
     */
    var $_aFields;

    /**
     * @var int
     */
    var $displayOrder = 0;

    /**
     * A method to return the name of the plugin. Must be implemented
     * in children classes.
     *
     * @abstract
     * @return string A string describing the plugin class.
     */
    function getName()
    {
        OA::debug('Cannot run abstract method');
        exit();
    }

    /**
     * A method to prepare the array of translated column names that is associated
     * with the data that needs to be displayed by the calling OA_Admin_Statistics_Common
     * or child class.
     *
     * @param OA_Admin_Statistics_Common $oController The calling OA_Admin_Statistics_Common
     *                                                or child class.
     * @return array An array of fields, indexed by "field", giving the
     *               "short" name - {@see $this->_aFields}.
     */
    function getFields(&$oController)
    {
        // Get the preferences
        $aPref = $GLOBALS['_MAX']['PREF'];
        $aFields = array();
        foreach ($this->_aFields as $k => $v) {
            if (isset($v['ctrl']) && !is_a($oController, $v['ctrl'])) {
                continue;
            }
            if (isset($v['pref'])) {
                $var = $v['pref'];
                $aFields[$k] = !empty($aPref[$var.'_label']) ? $aPref[$var.'_label'] : '';
            }
            if (empty($aFields[$k])) {
                $aFields[$k] = isset($v['short']) ? $v['short'] : $v['name'];
            }
        }
        return $aFields;
    }

    /**
     * A method to prepare the array of partial URIs required so that data elements displayed
     * by the calling OA_Admin_Statistics_Common or child class can be displayed as links.
     *
     * @return array An array of fields, indexed by "field", giving the
     *               "link" value - {@see $this->_aFields}.
     */
    function getColumnLinks()
    {
        $aLinks = array();
        foreach ($this->_aFields as $k => $v) {
            if (!empty($v['link'])) {
                $aLinks[$k] = $v['link'];
            }
        }
        return $aLinks;
    }

    /**
     * A method to prepare the array of columns that should be displayed (ie. not hidden)
     * by the calling OA_Admin_Statistics_Common or child class.
     *
     * @return array An array of fields, indexed by "field", giving a true
     *               or false value for display - {@see $this->_aFields}.
     */
    function getVisibleColumns()
    {
        // Get the preferences
        $aPref = $GLOBALS['_MAX']['PREF'];
        $aColumns = array();
        foreach ($this->_aFields as $k => $v) {
            $aColumns[$k] = false;
            if (isset($v['pref'])) {
                $var = $v['pref'];
                if (isset($aPref[$var])) {
                    if ($aPref[$var] == -1) {
                        $aColumns[$k] = !empty($v['rank']);
                    } else {
                        $aColumns[$k] = phpAds_isUser($aPref[$var]);
                    }
                }
            }
        }
        return $aColumns;
    }

    /**
     * A method to prepare the array of columns with zero as the value in each
     * column.
     *
     * @return array An array of fields, indexed by "field", with "0" as the
     *               value in each column - {@see $this->_aFields}.
     */
    function getEmptyRow()
    {
        $aNames = array();
        foreach (array_keys($this->_aFields) as $k) {
            $aNames[$k] = 0;
        }
        return $aNames;
    }

    /**
     * A method that returns an array of parameters representing custom columns
     * to use to determine the span of history when displaying delivery statistics.
     *
     * That is, either an empty array if the delivery statistics plugin does not
     * need to alter the stanard span of delivery statistics, or, an array of two
     * elements:
     *
     *      'custom_table'   => The name of the table to look for data in to
     *                          determine if the span of the data to be shown needs
     *                          to be extended beyond the default; and
     *      'custom_columns' => An array of one element, "start_date", which is
     *                          indexed by SQL code that can be run to determine the
     *                          starting date in the span.
     *
     * For example, if you have a custom data table "foo", and the earliest date
     * in this table can be found by using the SQL "SELECT DATE_FORMAT(MIN(bar), '%Y-%m-%d')",
     * then the array to return would be:
     *
     * array(
     *      'custom_table'   => 'foo',
     *      'custom_columns' => array("DATE_FORMAT(MIN(bar), '%Y-%m-%d')" => 'start_date')
     * );
     *
     * @return array As described above.
     */
    function getHistorySpanParams()
    {
        return array();
    }

    /**
     * A method to format a row of statistics according to the column's "format"
     * value in the {@link $this->_aFields} array, and according to user preferences
     * for how numbers/currency should be formatted.
     *
     * @param array   $aRow    An array containing a row of statistics to format.
     * @param boolean $isTotal Is the row a "total" row? When true, ensures that
     *                         all "id" formatted columns (from the
     *                         {@link $this->_aFields} array) are set to "-".
     */
    function _formatStats(&$aRow, $isTotal = false)
    {
        foreach ($this->_aFields as $k => $v) {
            if (array_key_exists($k, $aRow)) {
                if ($v['format'] == 'id') {
                    $aRow[$k] = $isTotal ? '-' : $aRow[$k];
                } elseif ($aRow[$k] == 0) {
                    $aRow[$k] = '-';
                } elseif ($v['format'] == 'percent') {
                    $aRow[$k] = phpAds_formatPercentage($aRow[$k]);
                } elseif ($v['format'] == 'currency') {
                    $aRow[$k] = phpAds_formatNumber($aRow[$k], 2);
                } else {
                    $aRow[$k] = phpAds_formatNumber($aRow[$k]);
                }
            }
        }
    }
















    function getSumFieldNames()
    {
        $aFields = array();
        foreach ($this->_aFields as $k => $v) {
            if ($v['format'] != 'percent') {
                $aFields[] = $k;
            }
        }

        return $aFields;
    }

    function getPreferenceNames()
    {
        // Get the preferences
        $pref = $GLOBALS['_MAX']['PREF'];

        $prefs = array();
        foreach ($this->_aFields as $k => $v) {
            if (isset($v['pref'])) {
                $prefs[$k] = $v['pref'];
            }
        }

        return $prefs;
    }

    function getDefaultRanks()
    {
        $prefs = array();
        foreach ($this->_aFields as $k => $v) {
            if (isset($v['pref']) && isset($v['rank'])) {
                $prefs[$v['pref']] = $v['rank'];
            }
        }

        return $prefs;
    }

    function getVisibilitySettings()
    {
        $prefs = array();
        foreach ($this->_aFields as $v) {
            if (isset($v['pref'])) {
                $var = $v['pref'];
                $prefs[$var] = $v['name'];
            }
        }

        return $prefs;
    }

    /**
     * Return the active status of a row
     *
     * @param array Row of stats
     * @return boolean True if the row is active
     */
    function isRowActive($row)
    {
        foreach ($this->_aFields as $k => $v) {
            if (!empty($v['active']) && $row[$k] > 0) {
                return true;
            }
        }

        return false;
    }

    function addQueryParams(&$aParams)
    {
    }

    function mergeData(&$aRows, $method, $aParams)
    {
    }

    /**
     * Add the fields which require calculations
     *
     * @param array Row of stats
     */
    function summarizeStats(&$row)
    {
        OA::debug('Cannot run abstract method');
        exit();
    }

    /**
     * Return plugin column formats
     *
     * @param array Formats
     */
    function getFormats()
    {
        $ret[] = array();

        foreach ($this->_aFields as $k => $v) {
            $ret[$k] = $v['format'];
        }

        return $ret;
    }

    /**
     * Add the fields needed for conversions stats
     *
     * @param array Row of stats
     * @param array Empty row
     * @param string Invocated method
     * @param array Parameter array
     */
    function mergeConversions(&$aRows, $emptyRow, $method, $aParams)
    {
        $conf = $GLOBALS['_MAX']['CONF'];

        $aParams['include'] = isset($aParams['include']) ? array_flip($aParams['include']) : array();
        $aParams['exclude'] = isset($aParams['exclude']) ? array_flip($aParams['exclude']) : array();

        // Primary key
        if ($method == 'getEntitiesStats') {
            if (!isset($aParams['exclude']['ad_id']) && !isset($aParams['exclude']['zone_id'])) {
                $aFields[] = "CONCAT(diac.ad_id, '_', diac.zone_id) AS pkey";
            } elseif (!isset($aParams['exclude']['ad_id'])) {
                $aFields[] = "diac.ad_id AS pkey";
            } else {
                $aFields[] = "diac.zone_id AS pkey";
            }
        } else {
            $aParams['exclude']['ad_id']   = true;
            $aParams['exclude']['zone_id'] = true;

            if ($method == 'getDayHistory') {
                $tzMethod    = 'format';
                $tzArgs      = array('%Y-%m-%d');
            } elseif ($method == 'getMonthHistory') {
                $tzMethod    = 'format';
                $tzArgs      = array('%Y-%m');
            } elseif ($method == 'getDayOfWeekHistory') {
                $tzMethod    = 'getDayOfWeek';
                $tzArgs      = array();
            } elseif ($method == 'getHourHistory') {
                $tzMethod    = 'getHour';
                $tzArgs      = array();
            }
        }

        $aFrom   = array(
            "{$conf['table']['prefix']}{$conf['table']['data_intermediate_ad_connection']} diac"
        );
        $aFields[] = "DATE_FORMAT(diac.tracker_date_time, '%Y-%m-%d %H:00:00') AS day_and_hour";
        $aWhere   = array("diac.inside_window = 1");
        $aGroupBy = array('day_and_hour');

        $aFields[] = "SUM(IF(diac.connection_status = ".MAX_CONNECTION_STATUS_APPROVED.
                        " AND diac.connection_action = ".MAX_CONNECTION_AD_IMPRESSION.",1,0)) AS sum_conversions_".MAX_CONNECTION_AD_IMPRESSION;
        $aFields[] = "SUM(IF(diac.connection_status = ".MAX_CONNECTION_STATUS_APPROVED.
                        " AND diac.connection_action = ".MAX_CONNECTION_AD_CLICK.",1,0)) AS sum_conversions_".MAX_CONNECTION_AD_CLICK;
        $aFields[] = "SUM(IF(diac.connection_status = ".MAX_CONNECTION_STATUS_APPROVED.
                        " AND diac.connection_action = ".MAX_CONNECTION_AD_ARRIVAL.",1,0)) AS sum_conversions_".MAX_CONNECTION_AD_ARRIVAL;
        $aFields[] = "SUM(IF(diac.connection_status = ".MAX_CONNECTION_STATUS_APPROVED.
                        " AND diac.connection_action = ".MAX_CONNECTION_MANUAL.",1,0)) AS sum_conversions_".MAX_CONNECTION_MANUAL;
        $aFields[] = "SUM(IF(diac.connection_status = ".MAX_CONNECTION_STATUS_APPROVED.",1,0)) AS sum_conversions";
        $aFields[] = "SUM(IF(diac.connection_status = ".MAX_CONNECTION_STATUS_PENDING.",1,0)) AS sum_conversions_pending";

        if (!empty($aParams['day_begin']) && !empty($aParams['day_end'])) {
            $oStartDate = & new Date("{$aParams['day_begin']} 00:00:00");
            $oEndDate   = & new Date("{$aParams['day_end']} 23:59:59");
            $oStartDate->toUTC();
            $oEndDate->toUTC();
            $aWhere[] = "diac.tracker_date_time BETWEEN '".$oStartDate->format('%Y-%m-%d %H:%M:%S')."'".
                        " AND '".$oEndDate->format('%Y-%m-%d %H:%M:%S')."'";
        }

        if (!empty($aParams['agency_id'])) {
            $aFrom['b'] = "JOIN {$conf['table']['prefix']}{$conf['table']['banners']} b ON (b.bannerid = diac.ad_id)";
            $aFrom['m'] = "JOIN {$conf['table']['prefix']}{$conf['table']['campaigns']} m ON (m.campaignid = b.campaignid)";
            $aFrom['c'] = "JOIN {$conf['table']['prefix']}{$conf['table']['clients']} c ON (c.clientid = m.clientid)";
            $aFrom['z'] = "LEFT JOIN {$conf['table']['prefix']}{$conf['table']['zones']} z ON (z.zoneid = diac.zone_id)";
            $aFrom['p'] = "LEFT JOIN {$conf['table']['prefix']}{$conf['table']['affiliates']} p ON (p.affiliateid = z.affiliateid AND p.agencyid = '{$aParams['agency_id']}')";

            $aWhere[] = "c.agencyid = '{$aParams['agency_id']}'";
        }
        if (!empty($aParams['advertiser_id']) || isset($aParams['include']['advertiser_id'])) {
            $aFrom['b'] = "JOIN {$conf['table']['prefix']}{$conf['table']['banners']} b ON (b.bannerid = diac.ad_id)";
            $aFrom['m'] = "JOIN {$conf['table']['prefix']}{$conf['table']['campaigns']} m ON (m.campaignid = b.campaignid)";

            if (!empty($aParams['advertiser_id'])) {
                $aWhere[] = "m.clientid = '{$aParams['advertiser_id']}'";
            }
            if (isset($aParams['include']['advertiser_id']) && !isset($aParams['exclude']['advertiser_id'])) {
                $aFields[]  = "m.clientid AS advertiser_id";
                $aGroupBy[] = "advertiser_id";
            }
        }
        if (!empty($aParams['placement_id']) || isset($aParams['include']['placement_id'])) {
            $aFrom['b'] = "JOIN {$conf['table']['prefix']}{$conf['table']['banners']} b ON (b.bannerid = diac.ad_id)";

            if (!empty($aParams['placement_id'])) {
                $aWhere[] = "b.campaignid = '{$aParams['placement_id']}'";
            }
            if (isset($aParams['include']['placement_id']) && !isset($aParams['exclude']['placement_id'])) {
                $aFields[]  = "b.campaignid AS placement_id";
                $aGroupBy[] = "placement_id";
            }
        }
        if (!empty($aParams['publisher_id']) || isset($aParams['include']['publisher_id'])) {
            $aFrom['z'] = "JOIN {$conf['table']['prefix']}{$conf['table']['zones']} z ON (z.zoneid = diac.zone_id)";

            if (!empty($aParams['publisher_id'])) {
                $aWhere[] = "z.affiliateid = '{$aParams['publisher_id']}'";
            }
            if (isset($aParams['include']['publisher_id']) && !isset($aParams['exclude']['publisher_id'])) {
                $aFields[]  = "z.affiliateid AS publisher_id";
                $aGroupBy[] = "publisher_id";
            }
        }
        if (!empty($aParams['ad_id'])) {
            $aWhere[] = "diac.ad_id = '{$aParams['ad_id']}'";
        }
        if (!isset($aParams['exclude']['ad_id'])) {
            $aFields[]  = "diac.ad_id AS ad_id";
            $aGroupBy[] = "ad_id";
        }
        // Using isset: zone_id could be 0 in case of direct selection
        if (isset($aParams['zone_id'])) {
            $aWhere[] = "diac.zone_id = '{$aParams['zone_id']}'";
        }
        if (!isset($aParams['exclude']['zone_id'])) {
            $aFields[]  = "diac.zone_id AS zone_id";
            $aGroupBy[] = "zone_id";
        }

        $sFields   = count($aFields)  ? join(', ', $aFields)  : '';
        $sFrom     = count($aFrom)    ? join(' ', $aFrom)   : '';
        $sWhere    = count($aWhere)   ? 'WHERE '.join(' AND ', $aWhere)   : '';
        $sGroupBy  = count($aGroupBy) ? 'GROUP BY '.join(', ', $aGroupBy) : '';

        $query = "SELECT ".$sFields." FROM ".$sFrom." ".$sWhere." ".$sGroupBy;

        $oDbh = OA_DB::singleton();
        $aResult = $oDbh->queryAll($query);
        if (PEAR::isError($aResult))
        {
            // if there is an error?
        }
        else
        {
            foreach ($aResult AS $k => $row) {
                unset($aResult[$k]);
                $aResult[$row['day_and_hour']] = $row;
            }
            $aResult = Admin_DA::_convertStatsArrayToTz($aResult, $aParams, $tzMethod, $tzArgs);
            foreach ($aResult AS $k => $row)
            {
                if (!isset($aRows[$k])) {
                    $aRows[$k] = $emptyRow;
                }

                $aRows[$k] = $row + $aRows[$k];
            }
        }
    }

}

?>