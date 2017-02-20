<?php


class Routes {

    public static function inviteToOrganizationConfirm( $requestInfo, Array $options = [] ) {

        $host = self::httpHost( $options );

        $jwtHandler = new SimpleJWT( [
                'invited_by_uid'  => $requestInfo[ 'invited_by_uid' ],
                'email'           => $requestInfo[ 'email' ],
                'organization_id' => $requestInfo[ 'organization_id' ],
        ] );

        $jwt = $jwtHandler->jsonSerialize();

        return "$host/api/app/orgs/members/invite/$jwt";

    }

    public static function passwordReset( $confirmation_token, $options = array() ) {
        $host = self::httpHost( $options );

        return "$host/api/app/user/password_reset/$confirmation_token";
    }

    public static function signupConfirmation( $confirmation_token, $options = array() ) {
        $host = self::httpHost( $options );

        return "$host/api/app/user/confirm/$confirmation_token";
    }

    /**
     * @param       $id_job
     * @param       $password
     * @param array $options
     *
     * @return string
     */
    public static function downloadXliff( $id_job, $password, $options = array() ) {
        $host = self::httpHost( $options );

        // TODO: pass in a filename here as last param?
        return "$host/SDLXLIFF/$id_job/$password/$id_job.zip";
    }


    public static function downloadOriginal( $id_job, $password, $filename = null, $download_type = 'all', $options = array() ) {
        $host = self::httpHost( $options );

        $params = array(
                'id_job'        => $id_job,
                'password'      => $password,
                'download_type' => $download_type
        );

        if ( !empty( $filename ) ) {
            $params[ 'filename' ] = $filename;
        }

        return "$host/?action=downloadOriginal&" . Utils::buildQueryString( $params );
    }


    public static function downloadTranslation( $id_job, $password, $id_file, $filename = null, $download_type = 'all', $options = array() ) {
        $host = self::httpHost( $options );

        $params = array(
                'id_job'        => $id_job,
                'id_file'       => $id_file,
                'password'      => $password,
                'download_type' => $download_type
        );

        if ( !empty( $filename ) ) {
            $params[ 'filename' ] = $filename;
        }

        return "$host/?action=downloadFile&" . Utils::buildQueryString( $params );
    }

    public static function revise( $project_name, $id_job, $password, $source, $target, $options = array() ) {
        $host = self::httpHost( $options );

        return "$host/revise/$project_name/$source-$target/$id_job-$password";
    }

    public static function translate( $project_name, $id_job, $password, $source, $target, $options = array() ) {
        $host = self::httpHost( $options );

        return "$host/translate/$project_name/$source-$target/$id_job-$password";
    }

    public static function analyze( $params, $options = array() ) {
        $params = \Utils::ensure_keys( $params,
                array( 'project_name', 'id_project', 'password' )
        );

        $host = self::httpHost( $options );

        $project_name = Utils::friendly_slug( $params[ 'project_name' ] );

        return $host . "/analyze/" . $project_name . "/" . $params[ 'id_project' ] . "-" . $params[ 'password' ];
    }

    public static function appRoot( $options = array() ) {
        $query = isset( $options[ 'query' ] ) ? $options[ 'query' ] : null;

        $url = self::httpHost( $options ) . \INIT::$BASEURL;

        if ( $query ) {
            $url .= '?' . http_build_query( $query );
        }

        return $url;
    }

    /**
     * @param $params
     *
     * @return string
     */
    public static function pluginsBase( $options = array() ) {
        return self::httpHost( $options ) . '/plugins';
    }


    public static function httpHost( $params ) {
        $host = INIT::$HTTPHOST;

        if ( !empty( $params[ 'http_host' ] ) ) {
            $host = $params[ 'http_host' ];
        }

        if ( empty( $host ) ) {
            throw new Exception( 'HTTP_HOST is not set ' );
        }

        return $host;
    }

}