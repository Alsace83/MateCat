<?php

class ManageUtils {

    /**
     * @param Users_UserStruct               $user
     * @param $start
     * @param $step
     * @param $search_in_pname
     * @param $search_source
     * @param $search_target
     * @param $search_status
     * @param $search_only_completed
     * @param $project_id
     * @param \Teams\TeamStruct|null $team
     * @param Users_UserStruct|null          $assignee
     * @param bool                           $no_assignee
     *
*@return array
     */
    public static function queryProjects(
            Users_UserStruct $user, $start, $step, $search_in_pname,
            $search_source, $search_target, $search_status, $search_only_completed,
            $project_id,
            \Teams\TeamStruct $team = null,
            Users_UserStruct $assignee = null,
            $no_assignee = false
    ) {

        $data = getProjects(
            $user, $start, $step, $search_in_pname, $search_source, $search_target,
            $search_status, $search_only_completed, $project_id, $team,
            $assignee, $no_assignee
        );

        $projects     = array();
        $projectIDs   = array();
        $project2info = array();

        //get project IDs from projects array
        foreach ( $data as $item ) {
            $projectIDs[ ] = $item[ 'pid' ];
        }

        if( empty( $projectIDs ) ){
            return array();
        }

        //get job data using job IDs
        $jobData = getJobsFromProjects( $projectIDs, $search_source, $search_target, $search_status, $search_only_completed );

        $dao = new Comments_CommentDao() ;
        $openThreads = $dao->getOpenThreadsForProjects( $projectIDs ) ;

        $dao = new \Translations\WarningDao() ;
        $warningsCount = $dao->getWarningsByProjectIds( $projectIDs ) ;

        $lang_handler = Langs_Languages::getInstance();

        //Prepare job data
        $project2jobChunk = array();
        foreach ( $jobData as $job_array ) {
            $job = array();

            /**
             * Assign job extracted variables
             */
            $job[ 'id' ]                    = $job_array[ 'id' ];
            $job[ 'pid' ]                   = $job_array[ 'id_project' ];
            $job[ 'password' ]              = $job_array[ 'password' ];
            $job[ 'source' ]                = $job_array[ 'source' ];
            $job[ 'target' ]                = $job_array[ 'target' ];
            $job[ 'subject' ]                = $job_array[ 'subject' ];
            $job[ 'sourceTxt' ]             = $lang_handler->getLocalizedName( $job[ 'source' ] );
            $job[ 'targetTxt' ]             = $lang_handler->getLocalizedName( $job[ 'target' ] );
            $job[ 'create_date' ]           = $job_array[ 'create_date' ];
            $job[ 'formatted_create_date' ] = self::formatJobDate( $job_array[ 'create_date' ] );
            $job[ 'job_first_segment' ]     = $job_array[ 'job_first_segment' ];
            $job[ 'job_last_segment' ]      = $job_array[ 'job_last_segment' ];
            $job[ 'mt_engine_name' ]        = $job_array[ 'name' ];
            $job[ 'id_tms' ]                = $job_array[ 'id_tms' ];


            $job[ 'open_threads_count' ] = 0 ;
            foreach( $openThreads as $openThread ) {
                if ( $openThread[ 'id_job' ] == $job[ 'id' ] && $openThread[ 'password' ] == $job[ 'password' ] ) {
                    $job[ 'open_threads_count' ] = (int) $openThread[ 'count' ] ;
                }
            }

            $job['warnings_count'] = 0;
            foreach( $warningsCount as $count ) {
                if ( $count[ 'id_job' ] == $job[ 'id' ] && $count[ 'password' ] == $job[ 'password' ] ) {
                    $job[ 'warnings_count' ] = (int) $count[ 'count' ] ;
                    $job[ 'warning_segments' ] = array_map( function( $id_segment ){ return (int)$id_segment; }, explode( ",", $count[ 'segment_list' ] ) );
                }
            }


            $job['quality_overall'] = CatUtils::getQualityOverallFromJobArray( $job_array ) ;


            //generate and set job stats
            $jobStats = new WordCount_Struct();
            $jobStats->setIdJob( $job_array[ 'id' ] );
            $jobStats->setDraftWords( $job_array[ 'DRAFT' ] );
            $jobStats->setRejectedWords( $job_array[ 'REJECT' ] );
            $jobStats->setTranslatedWords( $job_array[ 'TRANSLATED' ] );
            $jobStats->setApprovedWords( $job_array[ 'APPROVED' ] );

            //These would be redundant in response. Unset them.
            unset( $job_array[ 'DRAFT' ] );
            unset( $job_array[ 'REJECT' ] );
            unset( $job_array[ 'TRANSLATED' ] );
            unset( $job_array[ 'APPROVED' ] );

            $job[ 'stats' ] = CatUtils::getFastStatsForJob( $jobStats );

            //generate and set job tm_keys
            $tm_keys_json = $job_array[ 'tm_keys' ];

            $tm_keys_json = TmKeyManagement_TmKeyManagement::getOwnerKeys( array( $tm_keys_json ) );

            $tm_keys = array();

            foreach ( $tm_keys_json as $tm_key_struct ) {
                /**
                 * @var $tm_key_struct TmKeyManagement_TmKeyStruct
                 */
                $tm_keys[ ] = array(
                        "key" => $tm_key_struct->key,
                        "r"   => ( $tm_key_struct->r ) ? 'Lookup' : '&nbsp;',
                        "w"   => ( $tm_key_struct->w ) ? 'Update' : '',
                        "name" => $tm_key_struct->name
                );
            }

            $job[ 'private_tm_key' ] = json_encode( $tm_keys );

            $job[ 'disabled' ] = ( $job_array[ 'status_owner' ] == Constants_JobStatus::STATUS_CANCELLED ) ? "disabled" : "";
            $job[ 'status' ]   = $job_array[ 'status_owner' ];
            $job[ 'show_download_xliff'] =
              (INIT::$DEPRECATE_LEGACY_XLIFFS == false ||
                Utils::isJobBasedOnMateCatFilters($job_array[ 'id' ]) == true);


            //These vars will be used in projects loop for some flag evaluation.
            $project2info[ $job[ 'pid' ] ][ 'status' ][ ]      = $job[ 'status' ];
            $project2info[ $job[ 'pid' ] ][ 'mt_engine_name' ] = $job[ 'mt_engine_name' ];
            $project2info[ $job[ 'pid' ] ][ 'id_tms' ]         = $job[ 'id_tms' ];

            $project2jobChunk[ $job[ 'pid' ] ][] = $job;

        }

        $dao = new Projects_ProjectDao() ;
        $remoteFileServices = $dao->getRemoteFileServiceName( $projectIDs ) ;

        //Prepare project data
        foreach ( $data as $item ) {

            $project                     = array();
            $project[ 'id' ]             = $item[ 'pid' ];
            $project[ 'name' ]           = $item[ 'name' ];
            $project[ 'id_team' ]        = (int) $item[ 'id_team' ] ;

            $project[ 'jobs' ]           = array();
            $project[ 'no_active_jobs' ] = true;
            $project[ 'has_cancelled' ]  = 0;
            $project[ 'has_archived' ]   = 0;
            $project[ 'create_date' ]    = $item[ 'create_date' ];
            $project[ 'password' ]       = $item[ 'password' ];
            $project[ 'tm_analysis' ]    = number_format( $item[ 'tm_analysis_wc' ], 0, ".", "," );

            $project[ 'jobs' ] = $project2jobChunk[ $project[ 'id' ] ];

            $project[ 'no_active_jobs' ] = ( $project[ 'no_active_jobs' ] ) ? ' allCancelled' : '';

            $project2info[ $project[ 'id' ] ][ 'status' ] = array_unique( $project2info[ $project[ 'id' ] ][ 'status' ] );

            $project[ 'no_active_jobs' ] = ( !in_array( Constants_JobStatus::STATUS_ACTIVE, $project2info[ $project[ 'id' ] ][ 'status' ] ) ) ? ' allCancelled' : '';;
            $project[ 'has_cancelled' ]  = ( in_array( Constants_JobStatus::STATUS_CANCELLED, $project2info[ $project[ 'id' ] ][ 'status' ] ) );
            $project[ 'has_archived' ]   = ( in_array( Constants_JobStatus::STATUS_ARCHIVED, $project2info[ $project[ 'id' ] ][ 'status' ] ) );
            $project[ 'mt_engine_name' ] = $project2info[ $project[ 'id' ] ][ 'mt_engine_name' ];
            $project[ 'id_tms' ]         = $project2info[ $project[ 'id' ] ][ 'id_tms' ];

            $project[ 'features' ] = $item[ 'features' ] ;
            $project[ 'id_assignee' ] = $item[ 'id_assignee' ] ;
            $project[ 'project_slug' ] = Utils::friendly_slug( $project['name'] ) ;
            $project['remote_file_service'] = null ;

            foreach( $remoteFileServices as $service ) {
                if ( $project['id'] == $service['id_project'] ) {
                    $project['remote_file_service'] = $service['service'] ;
                }
            }

            $projects[ ] = $project;
        }

        return $projects;

    }

    /**
     * Formats a date for user visualization.
     *
     * @param $my_date        string A date in mysql format. <br/>
     *                        <b>E,g.</b> 2014-01-01 23:59:48
     *
     * @return string A formatted date
     */
    private static function formatJobDate( $my_date ) {

        $date          = new DateTime( $my_date );
        $formattedDate = $date->format( 'Y M d H:i' );

        $now       = new DateTime();
        $yesterday = $now->sub( new DateInterval( 'P1D' ) );

        //today
        if ( $now->format( 'Y-m-d' ) == $date->format( 'Y-m-d' ) ) {
            $formattedDate = "Today, " . $date->format( 'H:i' );
        } //yesterday
        else {
            if ( $yesterday->format( 'Y-m-d' ) == $date->format( 'Y-m-d' ) ) {
                $formattedDate = 'Yesterday, ' . $date->format( 'H:i' );
            } //this month
            else {
                if ( $now->format( 'Y-m' ) == $date->format( 'Y-m' ) ) {
                    $formattedDate = $date->format( 'M d, H:i' );
                } //this year
                else {
                    if ( $now->format( 'Y' ) == $date->format( 'Y' ) ) {
                        $formattedDate = $date->format( 'M d, H:i' );
                    }
                }
            }
        }

        return $formattedDate;

    }

}
