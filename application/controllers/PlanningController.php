<?php
/**
 * flightzilla
 *
 * Copyright (c)2012, Hans-Peter Buniat <hpbuniat@googlemail.com>.
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
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

/**
 * Ressource-planning
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class PlanningController extends Zend_Controller_Action {

    /**
     * @var Model_Ticket_Source_Bugzilla
     */
    private $_oBugzilla;

    /**
     *
     */
    public function init() {
        if (Zend_Auth::getInstance()->hasIdentity() === true) {
            $this->_oBugzilla = new Model_Ticket_Source_Bugzilla();
            $this->view->mode = $this->getRequest()->getActionName();
        }
        else {
            $this->_redirect('/index/login');
        }
    }

    /**
     *
     */
    public function __call($sName, $aArgs) {
        $this->_redirect('/');
    }

    public function dataAction() {
        $this->_oBugzilla->setView($this->view);

        $aTeam = $this->_oBugzilla->getTeam();
        $oRessource = new Model_Ressource_Manager();
        foreach ($aTeam as $sName) {
            $oRessource->registerRessource(Model_Ressource_Builder::build($sName));
        }

        $aTickets = $this->_oBugzilla->getAllBugs();
        foreach ($aTickets as $oTicket) {
            $oRessource->addTicket($oTicket);
        }

        $this->view->aRessource = $oRessource->getRessource($this->_getParam('name'));
    }

    /**
     *
     */
    public function projectsAction() {
        $this->_oBugzilla->setView($this->view);
        Zend_Debug::dump($this->_oBugzilla->getFirstWorkedDate(), __FILE__ . ':' . __LINE__);

        $oProject = new Model_Project_Container($this->_oBugzilla);
        $oProject->setup()->sortThemes();

        $this->view->aStack = $oProject->getProjectsAsStack();
        $this->view->aErrors = $oProject->getErrors();
        $this->view->aProjects = $oProject->getProjects();
    }

    /**
     *
     */
    public function sprintAction() {
        $this->view->aStack = $this->_oBugzilla->getTeam();
    }
}

