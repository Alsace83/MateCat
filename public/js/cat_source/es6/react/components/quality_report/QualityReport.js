import JobSummary from "./JobSummary";
import SegmentsDetails from "./SegmentsDetailsContainer";
import ReactDom from "react-dom";
import QRActions from "./../../actions/QualityReportActions";
import QRStore from "./../../stores/QualityReportStore";
import QRConstants from "./../../constants/QualityReportConstants";
import Header from "./../header/Header";
import QRApi from "../../ajax_utils/quality_report/qrUtils";


class QualityReport extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            segmentFiles: null,
            jobInfo: null
        };
        this.renderSegmentsFiles = this.renderSegmentsFiles.bind(this);
        this.renderJobInfo = this.renderJobInfo.bind(this);
    }

    renderSegmentsFiles(files) {
        this.setState({
            segmentFiles: files
        });
    }
    renderJobInfo(jobInfo) {
        this.setState({
            jobInfo: jobInfo
        });
    }

    componentWillMount() {
        QRActions.loadInitialAjaxData();
    }

    componentDidMount() {
        QRStore.addListener(QRConstants.RENDER_SEGMENTS, this.renderSegmentsFiles);
        QRStore.addListener(QRConstants.RENDER_REPORT, this.renderJobInfo);
        // console.log("Render Quality Report");
    }
    componentWillUnmount() {
        QRStore.removeListener(QRConstants.RENDER_SEGMENTS, this.renderSegmentsFiles);
        QRStore.removeListener(QRConstants.RENDER_REPORT, this.renderJobInfo);
    }

    render () {

        return <div className="qr-container">
                <div className="qr-container-inside">
                    <div className="qr-job-summary-container">
                        <div className="qr-bg-head"/>
                            <div className="qr-job-summary">
                                <h3>Job Summary</h3>
                                <JobSummary jobInfo={this.state.jobInfo}/>
                                <SegmentsDetails files={this.state.segmentFiles}/>
                            </div>
                    </div>

                </div>
            </div>
    }
}

export default QualityReport ;

ReactDom.render(React.createElement(QualityReport), document.getElementById('qr-root'));

let headerMountPoint = $("header")[0];

if (config.isLoggedIn) {
    QRApi.getUserData().done(function ( data ) {
        ReactDOM.render(React.createElement(Header, {
            showJobInfo: true,
            showModals: true,
            showTeams: false,
            user: data
        }), headerMountPoint);
    });
} else {
    ReactDOM.render(React.createElement(Header, {
        showJobInfo: true,
        showModals: true,
        showTeams: false,
        loggedUser: false,
    }), headerMountPoint);
}