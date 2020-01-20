<?php
/**
 * Created by PhpStorm.
 * @author ostico domenico@translated.net / ostico@gmail.com
 * Date: 29/11/19
 * Time: 17:00
 *
 */

namespace Features\TranslationVersions;


use Translations_SegmentTranslationStruct;

class EmptySegmentTranslationVersionHandler implements VersionHandlerInterface {

    /**
     * @param Translations_SegmentTranslationStruct $propagation
     */
    public function savePropagationVersions( Translations_SegmentTranslationStruct $propagation ) {}

    /**
     * Evaluates the need to save a new translation version to database.
     * If so, sets the new version number on $new_translation.
     *
     * Never set new Version
     *
     * @param Translations_SegmentTranslationStruct $new_translation
     * @param Translations_SegmentTranslationStruct $old_translation
     *
     * @return bool
     */
    public function evaluateVersionSave( Translations_SegmentTranslationStruct $new_translation, Translations_SegmentTranslationStruct $old_translation ) {
        return false;
    }
}