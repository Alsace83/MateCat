import AppDispatcher from '../dispatcher/AppDispatcher';
import QRApi from "../ajax_utils/quality_report/qrUtils";
import QRConstants from "./../constants/QualityReportConstants"
let QualityReportActions =  {

    loadInitialAjaxData() {
        QRApi.getSegmentsFiles().done(function ( response ) {
            if ( response.files ) {
                AppDispatcher.dispatch({
                    actionType: QRConstants.RENDER_SEGMENTS,
                    files: response.files,
                });
            }
        });
    },

    getMoreQRSegments() {
        QRApi.getSegmentsFiles2().done(function ( response ) {
            if ( response.files ) {
                AppDispatcher.dispatch({
                    actionType: QRConstants.ADD_SEGMENTS,
                    files: response.files,
                });
            }
        });
    }

}

export default QualityReportActions ;
