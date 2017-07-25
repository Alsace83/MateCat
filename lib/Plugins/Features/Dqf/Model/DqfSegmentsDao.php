<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 20/07/2017
 * Time: 12:10
 */

namespace Features\Dqf\Model;


use DataAccess_AbstractDao;
use PDO;

class DqfSegmentsDao extends DataAccess_AbstractDao {
    const TABLE = 'dqf_segments';

    protected static $primary_keys = ['id_segment'];
    protected static $auto_increment_fields = [] ;

    public function getByIdSegment( $id_segment ) {
        $sql = "SELECT * FROM dqf_segments WHERE id_segment = ?" ;

        $conn = $this->getConnection()->getConnection() ;
        $stmt = $conn->prepare( $sql );
        $stmt->execute( [ $id_segment ] );

        $stmt->setFetchMode( PDO::FETCH_CLASS, '\Features\Dqf\Model\DqfSegmentsStruct' ) ;

        return $stmt->fetch();
    }

    /**
     * Returns a map that is an array whith key = id_segment and value = id_dqf_segment ;
     *
     * @param $min
     * @param $max
     *
     * @return array
     */
    public function getByIdSegmentRange( $min, $max ) {
        $sql = "SELECT * FROM dqf_segments WHERE id_segment >= ? AND id_segment <= ? " ;

        $conn = $this->getConnection()->getConnection() ;
        $stmt = $conn->prepare( $sql );
        $stmt->execute( [ $min, $max ] );

        $stmt->setFetchMode( PDO::FETCH_CLASS, '\Features\Dqf\Model\DqfSegmentsStruct' ) ;

        $result = [] ;
        while( $row = $stmt->fetch() ) {
            $result[ $row->id_segment ] = $row->id_dqf_segment ;
        }

        return $result ;
    }


    /**
     * @param array $structs
     */
    public function insertBulkMap( array $structs ) {
        $sql = " INSERT INTO dqf_segments VALUES " ;
        $sql .= implode(', ', array_fill( 0, count( $structs ), " ( ?, ? ) " ) );

        $conn = $this->getConnection()->getConnection() ;

        $stmt = $conn->prepare( $sql );
        $flattened_values = array_reduce( $structs, 'array_merge', array() );
        $stmt->execute( $flattened_values );
    }
}