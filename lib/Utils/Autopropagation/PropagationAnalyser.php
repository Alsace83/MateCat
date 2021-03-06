<?php

namespace Autopropagation;

use Translations_SegmentTranslationStruct;

class PropagationAnalyser {

    /**
     * @var int
     */
    private $propagatedIceCount = 0;

    /**
     * @var int
     */
    private $notPropagatedIceCount = 0;

    /**
     * @var int
     */
    private $propagatedCount = 0;

    /**
     * @var int
     */
    private $notPropagatedCount = 0;

    /**
     * @param Translations_SegmentTranslationStruct $parentSegmentTranslation
     * @param Translations_SegmentTranslationStruct[] $arrayOfSegmentTranslationToPropagate
     *
     * @return \Propagation_PropagationTotalStruct
     */
    public function analyse( Translations_SegmentTranslationStruct $parentSegmentTranslation, $arrayOfSegmentTranslationToPropagate ) {

        $propagation = new \Propagation_PropagationTotalStruct();

        if ( $parentSegmentTranslation->match_type !== 'ICE' || $parentSegmentTranslation->locked != 1 ) { // remove ICE
            foreach ( $arrayOfSegmentTranslationToPropagate as $segmentTranslation ) {

                if ( $this->detectIce( $segmentTranslation ) ) {
                    $propagation->addNotPropagatedIce($segmentTranslation);
                    $this->notPropagatedIceCount++;
                } else {
                    $propagation->addPropagatedNotIce($segmentTranslation);
                    $propagation->addPropagatedId($segmentTranslation->id_segment);
                    $this->propagatedCount++;
                }
            }
        } else { // keep only ICE with the corresponding hash
            foreach ( $arrayOfSegmentTranslationToPropagate as $segmentTranslation ) {

                if ( $this->detectMatchingIce( $parentSegmentTranslation, $segmentTranslation ) ) {
                    $propagation->addPropagatedIce($segmentTranslation);
                    $propagation->addPropagatedId($segmentTranslation->id_segment);
                    $this->propagatedIceCount++;
                } else {
                    $propagation->addNotPropagatedNotIce($segmentTranslation);
                    $this->notPropagatedCount++;
                }
            }
        }

        return $propagation;
    }

    /**
     * @param Translations_SegmentTranslationStruct $segmentTranslation
     *
     * @return bool
     */
    private function detectIce( Translations_SegmentTranslationStruct $segmentTranslation ) {
        return ( $segmentTranslation->match_type === 'ICE' and $segmentTranslation->locked == 1 and $segmentTranslation->id_segment !== null );
    }

    /**
     * @param Translations_SegmentTranslationStruct $segmentTranslation
     *
     * @return bool
     */
    private function detectUnlockedIce( Translations_SegmentTranslationStruct $segmentTranslation ) {
        return ( $segmentTranslation->match_type === 'ICE' and $segmentTranslation->locked == 0 and $segmentTranslation->id_segment !== null );
    }

    /**
     * @param Translations_SegmentTranslationStruct $parentSegmentTranslation
     * @param Translations_SegmentTranslationStruct $segmentTranslation
     *
     * @return bool
     */
    private function detectMatchingIce( Translations_SegmentTranslationStruct $parentSegmentTranslation, Translations_SegmentTranslationStruct $segmentTranslation ) {
        return ( $segmentTranslation->match_type === 'ICE' and $segmentTranslation->locked == 1 and $segmentTranslation->segment_hash === $parentSegmentTranslation->segment_hash and
                $segmentTranslation->id_segment !== null );
    }
}
