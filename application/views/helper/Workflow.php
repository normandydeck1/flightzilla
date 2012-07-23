<?php
class View_Helper_Workflow extends Zend_View_Helper_Abstract {

    /**
     * Get the workflow-stats of the bug
     *
     * @param  Model_Ticket_Type_Bug $oBug
     *
     * @return string
     */
    public function workflow(Model_Ticket_Type_Bug $oBug) {
        $sClasses = 'prio' . $oBug->priority . ' ';

        if ($oBug->isQuickOne() === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_QUICK . ' ';
        }

        if ($oBug->isFailed() === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_FAILED . ' ';
        }

        if ($oBug->isMergeable() === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_MERGE . ' ';
        }

        if ($oBug->isOnlyTranslation() === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_TRANSLATION . ' ';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_SCREEN,'?') === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_SCREEN . ' ';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_COMMENT,'?') === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_COMMENT . ' ';
        }

        return $sClasses;
    }
}