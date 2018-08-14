/*
    Common events used in translation and revise page when Review Extended is active
 */

if (ReviewExtended.enabled()) {
    $(document).on('files:appended', function () {
        if (config.isReview) {
            SegmentActions.mountTranslationIssues();
            ReviewExtended.getSegmentsIssues();
        }
    });

    $( window ).on( 'segmentClosed', function ( e ) {
        SegmentActions.closeSegmentIssuePanel(UI.getSegmentId(e.segment));
    } );

    /*
    To close the issue panel when clicking on different segment.
     TODO: we still dont know when a segment is opened in the Segment component,
     i need this trick to know when close the panel
     */
    $( window ).on( 'segmentOpened', function ( e ) {
        if ( $(e.segment.el).find('.review-balloon-container').length === 0 &&
            !$(e.segment.el).find('.revise-button').hasClass('open')) {
            UI.closeIssuesPanel();
        }
    } );

    $(document).on('translation:change', function(e, data) {
        if (data.sid === UI.getSegmentId(UI.currentSegment)) {
            UI.getSegmentVersionsIssues(data.sid, UI.getSegmentFileId(data.segment));
        }
    });
}