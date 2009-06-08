<?php

/**
 * Finding
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5441 2009-01-30 22:58:43Z jwage $
 */
class Finding extends BaseFinding
{
    /**
     * get the detailed status of a Finding
     *
     * @return string
     */
    public function getStatus()
    {
        if (!in_array($this->status, array('MSA', 'EA'))) {
            return $this->status;
        } else {
            return Doctrine::getTable('Evaluation')->find($this->currentEvaluationId)->nickname;
        }
    }

    /**
     * Approve the current evaluation,
     * then update the status to either point to
     * a new Evaluation or else to change the status to DRAFT, EN,
     * or CLOSED as appropriate
     * 
     * @param int $uid a specific user id
     *
     * @return void
     */
    public function approve($uid)
    {
        if (is_null($this->currentEvaluationId) || !in_array($this->status, array('MSA', 'EA'))) {
            throw new Fisma_Exception_General("The finding can't be approved");
        }
        $evidenceId = null;
        $evaluation = new Evaluation();
        $group = $evaluation->getTable()->find($this->currentEvaluationId)->approvalGroup;
        if ('evidence' == $group) {
            $evidenceId = $this->Evidence->getLast()->id;
        }
        $findingEvaluation = new FindingEvaluation();
        $findingEvaluation->findingId    = $this->id;
        $findingEvaluation->evidenceId   = $evidenceId;
        $findingEvaluation->evaluationId = $this->currentEvaluationId;
        $findingEvaluation->decision     = 'APPROVED';
        $findingEvaluation->userId       = $uid;
        $findingEvaluation->save();

        if ('MSA' == $this->status) {
            if ($this->currentEvaluationId == Doctrine::getTable('Evaluation')
                                                ->findByDql('approvalGroup = "action"')
                                                ->count()) {
                $this->status = 'EN';
            }
        }
        if ('EA' == $this->status) {
            if ($this->currentEvaluationId == Doctrine::getTable('Evaluation')->count()) {
                $this->status = 'CLOSED';
            }
        }
        if ('CLOSED' != $this->status) {
            $this->currentEvaluationId += 1;
        }
        $this->save();
    }

    /**
     * Deny the current evaluation
     *
     * @param int $uid a specific user id
     * @param string $comment deny comment
     * @return void
     */
    public function deny($uid, $comment)
    {
        if (is_null($this->currentEvaluationId) || !in_array($this->status, array('MSA', 'EA'))) {
            throw new Fisma_Exception_General("The finding can't be denied");
        }
        $evidenceId  = $this->Evidence->getLast()->id;

        $findingEvaluation = new FindingEvaluation();
        $findingEvaluation->findingId    = $this->id;
        $findingEvaluation->evidenceId   = $evidenceId;
        $findingEvaluation->evaluationId = $this->currentEvaluationId;
        $findingEvaluation->decision     = 'DENIED';
        $findingEvaluation->userId       = $uid;
        $findingEvaluation->comment      = $comment;
        $findingEvaluation->save();

        if ('MSA' == $this->status) {
            $this->status              = 'DRAFT';
            $this->currentEvaluationId = null;
        }
        if ('EA' == $this->status) {
            $firstEaEvaluationId = Doctrine::getTable('Evaluation')->findByDql('approvalGroup = "evidence"')->count();
            $this->status              = 'EN';
            $this->currentEvaluationId = $firstEaEvaluationId;
        }
        $this->save();
    }

}
