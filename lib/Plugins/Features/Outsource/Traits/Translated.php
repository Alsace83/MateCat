<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 10/04/2018
 * Time: 11:40
 */

namespace Features\Outsource\Traits;

use API\V2\Json\ProjectUrls;
use DataAccess\ShapelessConcreteStruct;
use Email\AbstractEmail;
use MultiCurlHandler;
use Outsource\TranslatedConfirmationStruct;
use Outsource\ConfirmationDao;
use \Exception;
use Utils;
use \INIT;

/**
 * Trait Translated
 *
 * This trait is meant to be used as Features\BaseFeature concrete class
 *
 * @package Features\Outsource\Traits
 */
trait Translated {

    protected $successEmailObject;
    protected $failureEmailObject;

    protected $internal_project_id;
    protected $internal_job_id;
    protected $external_project_id;

    private $config;

    public function setSuccessMailSender( AbstractEmail $emailObject ) {
        $this->successEmailObject = $emailObject;
    }

    public function setFailureMailSender( AbstractEmail $emailObject ) {
        $this->failureEmailObject = $emailObject;
    }

    public function setInternalIdProject( $id ) {
        $this->internal_project_id = $id;
        $this->successEmailObject->setInternalIdProject( $id );
        $this->failureEmailObject->setInternalIdProject( $id );
    }

    public function setInternalJobId( $id ) {
        $this->successEmailObject->setInternalJobId( $id );
        $this->failureEmailObject->setInternalJobId( $id );
        $this->internal_job_id = $id;
    }

    public function setExternalProjectId( $id ) {
        $this->external_project_id = $id;
        $this->successEmailObject->setExternalProjectId( $id );
        $this->failureEmailObject->setExternalProjectId( $id );
    }

    public function getInternalIdProject(){
        return $this->internal_project_id;
    }

    public function getInternalJobId(){
        return $this->internal_job_id;
    }

    public function getExternalProjectId(){
        return $this->external_project_id;
    }

    public function requestProjectQuote( $project_id, $_analyzed_report ) {

        $this->setInternalIdProject( $project_id );
        $eq_words_count = [];
        foreach ( $_analyzed_report as $job_info ) {
            $eq_words_count[ $job_info[ 'id_job' ] ] = number_format( $job_info[ 'eq_wc' ] + 0.00000001, 0, ".", "," );
        }

        $jobs = ( new \Jobs_JobDao() )->getByProjectId( $project_id, 3600 );
        /** @var $jobs \Jobs_JobStruct[] */
        $project = $jobs[ 0 ]->getProject();

        /**
         * @var $projectData ShapelessConcreteStruct[]
         */
        $projectData = ( new \Projects_ProjectDao() )->setCacheTTL( 60 * 60 * 24 )->getProjectData( $project->id, $project->password );
        $formatted   = new ProjectUrls( $projectData );

        //Let the Feature Class decide about Urls
        $formatted = $this->projectUrls( $formatted );

        $this->config = self::getConfig();

        foreach ( $jobs as $job ) {

            $this->requestJobQuote($job, $eq_words_count[$job['id']], $project, $formatted);

        }

    }

    public function requestJobQuote(\Jobs_JobStruct $job, $eq_word, $project, $formatted_urls){

        $this->setInternalIdProject( $job->id_project );

        $this->setInternalJobId( $job->id );

        $quote_url = "http://www.translated.net/hts/index.php?" . http_build_query( [
                        'f'             => 'quote',
                        'cid'           => $this->config[ 'translated_username' ],
                        'p'             => $this->config[ 'translated_password' ],
                        's'             => $job->source,
                        't'             => $job->target,
                        'pn'            => $project->name,
                        'w'             => $eq_word,
                        'df'            => 'matecat',
                        'matecat_pid'   => $project->id,
                        'matecat_ppass' => $project->password,
                        'matecat_pname' => $project->name,
                        'subject'       => $job->subject,
                        'jt'            => 'T',
                        'fd'            => 0,
                        'of'            => 'json'
                ], PHP_QUERY_RFC3986 );

        try {
            $quote_response = json_decode( self::request( $quote_url ) );
            Utils::raiseJsonExceptionError();
            if ( $quote_response->code != 1 ) {
                throw new Exception( $quote_response->message );
            }
        } catch ( Exception $e ) {
            $this->failureEmailObject->setErrorMessage( $e->getMessage() );
            $this->failureEmailObject->send();

            return;
        }

        $this->setExternalProjectId( $quote_response->pid );

        /** @var $formatted ProjectUrls */
        $urls = $formatted_urls->render( true )[ 'jobs' ][ $job->id ][ 'chunks' ][ $job->password ];

        $confirmation_url = "http://www.translated.net/hts/index.php?" . http_build_query( [
                        'f'    => 'confirm',
                        'cid'  => $this->config[ 'translated_username' ],
                        'p'    => $this->config[ 'translated_password' ],
                        'pid'  => $quote_response->pid,
                        'c'    => 1,
                        'of'   => "json",
                        'urls' => json_encode( $urls )
                ], PHP_QUERY_RFC3986 );

        try {
            $response              = self::request( $confirmation_url );
            $confirmation_response = json_decode( $response );
            Utils::raiseJsonExceptionError();
            if ( $confirmation_response->code != 1 ) {
                throw new Exception( $confirmation_response->message );
            }
            $this->successEmailObject->send();
        } catch ( Exception $e ) {
            $this->failureEmailObject->setErrorMessage( $e->getMessage() );
            $this->failureEmailObject->send();

            return;
        }

        $confirmationStruct = new TranslatedConfirmationStruct( [
                'id_job'        => $job->id,
                'password'      => $job->password,
                'delivery_date' => $quote_response->delivery_date,
                'price'         => $quote_response->total,
                'quote_pid'     => $quote_response->pid
        ] );
        $cDao               = new ConfirmationDao;
        $cDao->insertStruct( $confirmationStruct, [ 'ignore' => true, 'no_nulls' => true ] );

        $outsourceDao = new ConfirmationDao();
        $outsourceDao->destroyConfirmationCache( $job );

        return true;

    }

    public static function get_class_name() {
        return ( new \ReflectionClass( get_called_class() ) )->getShortName();
    }

    public static function request( $url ) {

        $mh = new MultiCurlHandler();

        $curlOptions = [
                CURLOPT_HEADER         => 0,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPGET        => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_USERAGENT      => INIT::MATECAT_USER_AGENT . INIT::$BUILD_NUMBER,
                CURLOPT_CONNECTTIMEOUT => 5
        ];

        $token = $mh->createResource( $url, $curlOptions );

        $mh->multiExec();

        if ( $mh->hasError( $token ) ) {
            $error = $mh->getError( $token );
            throw new Exception( $error[ 'error' ] );
        }

        return $mh->getSingleContent( $token );
    }
}