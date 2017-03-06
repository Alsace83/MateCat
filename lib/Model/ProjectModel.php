<?php

use Features\QaCheckBlacklist\BlacklistFromZip;
use Teams\MembershipDao;
use Teams\MembershipStruct;

class ProjectModel {

    /**
     * @var Projects_ProjectStruct
     */
    protected $project_struct;

    protected $blacklist;

    protected $willChange = array();
    protected $changedFields = array();

    /**
     * @var Users_UserStruct
     */
    protected $user;

    public function __construct( Projects_ProjectStruct $project ) {
        $this->project_struct = $project;
    }

    public function getBlacklist() {
        // TODO: replace with check of file exitence, don't read whole file. 
        return BlacklistFromZip::getContent( $this->project_struct->getFirstOriginalZipPath() );
    }

    /**
     * Caches the information of blacklist file presence to project metadata.
     */
    public function saveBlacklistPresence() {
        $blacklist = $this->getBlacklist();

        if ( $blacklist ) {
            $this->project_struct->setMetadata( 'has_blacklist', '1' );
        }
    }

    public function resetUpdateList() {
        $this->willChange = array();
    }

    public function prepareUpdate( $field, $value ) {
        $this->willChange[ $field ] = $value;
    }

    public function setUser( $user ) {
        $this->user = $user ;
    }

    public function update() {
        $this->changedFields = array();

        $newStruct = new Projects_ProjectStruct( $this->project_struct->toArray() );

        if ( isset( $this->willChange[ 'name' ] ) ) {
            $this->checkName();
        }

        if ( isset( $this->willChange[ 'id_assignee' ] ) ) {
            $this->checkIdAssignee();
        }

        foreach ( $this->willChange as $field => $value ) {
            $newStruct->$field = $value;
        }

        $result = Projects_ProjectDao::updateStruct( $newStruct, array(
            'fields' => array_keys( $this->willChange )
        ) );

        if ( $result ) {
            $this->changedFields = $this->willChange ;
            $this->willChange = array();

            $this->_sendNotificationEmails();
        }

        // TODO: handle case of update failure

        $this->project_struct = $newStruct;

        return $this->project_struct;
    }

    protected function _sendNotificationEmails() {
        if (
            $this->changedFields['id_assignee'] &&
            !is_null($this->changedFields['id_assignee']) &&
            $this->user->uid != $this->changedFields['id_assignee']
        ) {
            $assignee = ( new Users_UserDao )->getByUid($this->changedFields['id_assignee']) ;
            $email = new \Email\ProjectAssignedEmail($this->user, $this->project_struct, $assignee );
            $email->send();
        }
    }

    private function checkName() {
        if ( empty( $this->willChange[ 'name' ] ) ) {
            throw new \Exceptions\ValidationError( 'Project name cannot be empty' );
        }
    }

    private function checkIdAssignee() {
        $membershipDao = new MembershipDao();
        $members       = $membershipDao->getMemberListByTeamId( $this->project_struct->id_team );
        $id_assignee   = $this->willChange[ 'id_assignee' ];
        $found         = array_filter( $members, function ( MembershipStruct $member ) use ( $id_assignee ) {
            return ( $id_assignee == $member->uid );
        } );

        if ( empty( $found ) ) {
            throw new \Exceptions\ValidationError( 'Assignee must be team member' );
        }

    }

}