<?php
/**
 * flightzilla
 *
 * Copyright (c) 2012-2013, Hans-Peter Buniat <hpbuniat@googlemail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the name of Hans-Peter Buniat nor the names of his
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package flightzilla
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace Flightzilla\Model\Stats;

use Flightzilla\Model\Ticket\Type\Bug;
use Flightzilla\Model\Ticket\Source\Bugzilla;

/**
 * Service for statistics
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Service {

    /**
     * The stat-identifiers
     *
     * @var string
     */
    const STATS_WORKFLOW = 'workflow';
    const STATS_CHUCK = 'chuck';
    const STATS_PRIORITIES = 'priority';
    const STATS_SEVERITIES = 'severity';
    const STATS_STATUS = 'status';
    const STATS_THROUGHPUT = 'throughput';
    const STATS_TYPE = 'throughput';

    /**
     * The time window for the ticket-throughput
     *
     * @var string
     */
    const THROUGHPUT_WINDOW = 'last monday';

    /**
     * The ticket-stack
     *
     * @var array
     */
    protected $_aStack = array();

    /**
     * The stack without closed tickets or container
     *
     * @var array
     */
    protected $_aFilteredStack = array();

    /**
     * The number of tickets
     *
     * @var int
     */
    protected $_iCount = 0;

    /**
     * Cache for stats
     *
     * @var array
     */
    protected $_aCache = array();

    /**
     * The configuration
     *
     * @var \Zend\Config\Config
     */
    protected $_config = null;

    /**
     * Create the stats-service
     *
     * @param  \Zend\Config\Config $oConfig
     */
    public function __construct(\Zend\Config\Config $oConfig) {
        $this->_config = $oConfig;
    }

    /**
     * Set the stack of tickets
     *
     * @param  array $aStack
     *
     * @return $this
     */
    public function setStack(array $aStack) {
        $this->_aStack = $aStack;
        $this->_aFilteredStack = array();

        foreach ($this->_aStack as $oBug) {
            /* @var $oBug Bug */
            if ($oBug->isClosed() !== true and $oBug->isContainer() !== true) {
                $this->_iCount++;
                $this->_aFilteredStack[] = $oBug;
            }
        }

        // flush the cache
        $this->_aCache = array();

        return $this;
    }

    /**
     * Is the stack empty?
     *
     * @return boolean
     */
    public function isStackEmpty() {
        return empty($this->_aStack);
    }

    /**
     * Get arbitrary stats by identifier
     *
     * @param  string $sStats
     *
     * @return array
     */
    public function get($sStats) {
        if (empty($this->_aCache[$sStats]) === true) {
            $this->_aCache[$sStats] = array();
        }

        return $this->_aCache[$sStats];
    }

    /**
     * Get the number of tickets
     *
     * @return int
     */
    public function getCount() {
        return $this->_iCount;
    }

    /**
     * Get the bug-stats
     *
     * @return array
     */
    public function getWorkflowStats() {

        if (empty($this->_aCache[self::STATS_WORKFLOW]) === true) {
            $this->_aCache[self::STATS_WORKFLOW] = array(
                Bug::WORKFLOW_ESTIMATED   => 0,
                Bug::WORKFLOW_ORGA        => 0,
                Bug::WORKFLOW_UNESTIMATED => 0,
                Bug::WORKFLOW_INPROGRESS  => 0,
                Bug::WORKFLOW_ACTIVE      => 0,
                Bug::WORKFLOW_TESTING     => 0,
                Bug::WORKFLOW_MERGE       => 0,
                Bug::WORKFLOW_DEADLINE    => 0,
                Bug::WORKFLOW_SCREEN      => 0,
                Bug::WORKFLOW_COMMENT     => 0,
                Bug::WORKFLOW_FAILED      => 0,
                Bug::WORKFLOW_QUICK       => 0,
                Bug::WORKFLOW_TRANSLATION => 0,
                Bug::WORKFLOW_TRANSLATION_PENDING => 0,
                Bug::WORKFLOW_TIMEDOUT    => 0,
            );

            $iTimeoutLimit = $this->_config->tickets->workflow->timeout;
            foreach ($this->_aFilteredStack as $oBug) {
                /* @var $oBug Bug */

                $bShouldHaveEstimation = true;
                if ($oBug->isOrga() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_ORGA]++;
                    $bShouldHaveEstimation = false;
                }

                if ($oBug->isEstimated()) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_ESTIMATED]++;
                }
                elseif ($bShouldHaveEstimation === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_UNESTIMATED]++;
                }

                if ($oBug->isWorkedOn()) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_INPROGRESS]++;
                }

                if ($oBug->isActive()) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_ACTIVE]++;
                }

                if ($oBug->hasFlag(Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_REQUEST) === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_TESTING]++;
                }

                if ($oBug->isFailed()) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_FAILED]++;
                }

                if ($oBug->isMergeable()) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_MERGE]++;
                }

                if ($oBug->deadlineStatus()) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_DEADLINE]++;
                }

                if ($oBug->hasFlag(Bug::FLAG_SCREEN, Bugzilla::BUG_FLAG_REQUEST)) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_SCREEN]++;
                }

                if ($oBug->hasFlag(Bug::FLAG_COMMENT, Bugzilla::BUG_FLAG_REQUEST) or $oBug->getStatus() === Bug::STATUS_CLARIFICATION) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_COMMENT]++;
                }

                if ($oBug->isQuickOne()) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_QUICK]++;
                }

                if ($oBug->isOnlyTranslation()) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_TRANSLATION]++;
                }

                if ($oBug->hasFlag(Bug::FLAG_TRANSLATION, Bugzilla::BUG_FLAG_REQUEST) === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_TRANSLATION_PENDING]++;
                }

                if ($oBug->isChangedWithinLimit($iTimeoutLimit) !== true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_TIMEDOUT]++;
                }
            }

            $this->_percentify($this->_aCache[self::STATS_WORKFLOW], $this->getCount());
        }

        return $this->_aCache[self::STATS_WORKFLOW];
    }

    /**
     * Return all statuses with percentage.
     *
     * @return array
     */
    public function getStatuses() {

        if (empty($this->_aCache[self::STATS_STATUS]) === true) {
            $this->_aCache[self::STATS_STATUS] = array();

            foreach ($this->_aFilteredStack as $oBug) {
                /* @var $oBug Bug */
                $sStatus = (string) $oBug->getStatus();
                if (empty($this->_aCache[self::STATS_STATUS][$sStatus]) === true) {
                    $this->_aCache[self::STATS_STATUS][$sStatus] = 0;
                }

                $this->_aCache[self::STATS_STATUS][$sStatus]++;
            }

            $this->_percentify($this->_aCache[self::STATS_STATUS], $this->getCount());
            ksort($this->_aCache[self::STATS_STATUS]);
        }

        return $this->_aCache[self::STATS_STATUS];
    }

    /**
     * Get the chuck-status
     *
     * @return string
     */
    public function getChuckStatus() {

        if (empty($this->_aCache[self::STATS_CHUCK]) === true) {

            $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::OK;
            if (empty($this->_aCache[self::STATS_WORKFLOW]) === true or empty($this->_aCache[self::STATS_STATUS]) === true) {
                $this->getWorkflowStats();
                $this->getStatuses();
            }

            $aWorkflow = $this->_aCache[self::STATS_WORKFLOW];
            $aStatus = $this->_aCache[self::STATS_STATUS];
            if (empty($aWorkflow[Bug::WORKFLOW_UNESTIMATED]) !== true and $aWorkflow[Bug::WORKFLOW_UNESTIMATED]['per'] > 10) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::WARN;
            }
            elseif (empty($aWorkflow[Bug::STATUS_UNCONFIRMED]) !== true and $aStatus[Bug::STATUS_UNCONFIRMED]['per'] > 10) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::WARN;
            }

            if (empty($aStatus[Bug::STATUS_REOPENED]) !== true and $aStatus[Bug::STATUS_REOPENED]['num'] > 1) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::ERROR;
            }
            elseif (empty($aWorkflow[Bug::WORKFLOW_FAILED]) !== true and $aWorkflow[Bug::WORKFLOW_FAILED]['per'] > 2) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::ERROR;
            }
            elseif (empty($aWorkflow[Bug::WORKFLOW_UNESTIMATED]) !== true and $aWorkflow[Bug::WORKFLOW_UNESTIMATED]['per'] > 15) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::WARN;
            }
            elseif (empty($aWorkflow[Bug::STATUS_UNCONFIRMED]) !== true and $aStatus[Bug::STATUS_UNCONFIRMED]['per'] > 15) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::WARN;
            }

            unset($aStatus, $aWorkflow);
        }

        return $this->_aCache[self::STATS_CHUCK];
    }

    /**
     * Get the priorities
     *
     * @return array
     */
    public function getPriorities() {

        if (empty($this->_aCache[self::STATS_PRIORITIES]) === true) {
            $this->_aCache[self::STATS_PRIORITIES] = array();

            foreach ($this->_aFilteredStack as $oBug) {
                /* @var $oBug Bug */
                $sPriority = $oBug->getPriority();
                if (empty($aPriorities[$sPriority]) === true) {
                    $this->_aCache[self::STATS_PRIORITIES][$sPriority] = 0;
                }

                $this->_aCache[self::STATS_PRIORITIES][$sPriority]++;
            }

            $this->_percentify($this->_aCache[self::STATS_PRIORITIES], $this->getCount());
            ksort($this->_aCache[self::STATS_PRIORITIES]);
        }

        return $this->_aCache[self::STATS_PRIORITIES];
    }

    /**
     * Get the priorities
     *
     * @return array
     */
    public function getSeverities() {

        if (empty($this->_aCache[self::STATS_SEVERITIES]) === true) {
            $this->_aCache[self::STATS_SEVERITIES] = array();

            foreach ($this->_aFilteredStack as $oBug) {
                /* @var $oBug Bug */
                $sSeverity = $oBug->getSeverity();
                if (empty($this->_aCache[self::STATS_SEVERITIES][$sSeverity]) === true) {
                    $this->_aCache[self::STATS_SEVERITIES][$sSeverity] = 0;
                }

                $this->_aCache[self::STATS_SEVERITIES][$sSeverity]++;
            }

            $this->_percentify($this->_aCache[self::STATS_SEVERITIES], $this->getCount());
            ksort($this->_aCache[self::STATS_SEVERITIES]);
        }

        return $this->_aCache[self::STATS_SEVERITIES];
    }

    /**
     * Get the ticket-throughput-diff for this week
     *
     * @return int
     */
    public function getThroughPut() {

        if (empty($this->_aCache[self::STATS_THROUGHPUT]) === true) {
            $this->_aCache[self::STATS_THROUGHPUT] = 0;
            $iCompare = strtotime(self::THROUGHPUT_WINDOW);
            foreach ($this->_aStack as $oTicket) {
                /* @var $oTicket Bug */
                if ($oTicket->isContainer() !== true) {
                    if ($oTicket->getCreationTime() > $iCompare) {
                        $this->_aCache[self::STATS_THROUGHPUT]++;
                    }

                    if ($oTicket->isStatusAtLeast(Bug::STATUS_RESOLVED) === true and $oTicket->getLastActivity() > $iCompare) {
                        $this->_aCache[self::STATS_THROUGHPUT]--;
                    }
                }
            }
        }

        return $this->_aCache[self::STATS_THROUGHPUT];
    }

    /**
     * Get the number of days which are used to determine the ticket-throughput
     *
     * @return int
     */
    public function getThroughPutDays() {
        return ceil((time() - strtotime(self::THROUGHPUT_WINDOW)) / 86400);
    }

    /**
     * Get the percentage of each type in the stack
     *
     * @param  array $aStack
     * @param  int   $iCount
     *
     * @return Bugzilla
     */
    protected function _percentify(array &$aStack, $iCount) {

        $mStat = null;
        foreach ($aStack as &$mStat) {
            $mStat = array(
                'num' => $mStat,
                'per' => ($iCount === 0) ? 0 : round(($mStat / $iCount) * 100, 2)
            );
        }

        unset($mStat);
        return $this;
    }
}
