
class ChangeProjectWorkspace extends React.Component {


    constructor(props) {
        super(props);
        this.state ={
            selectWorkspace: -1
        };
    }

    changeWorkspace() {
        ManageActions.changeProjectWorkspace(this.state.selectedWs,  this.props.project);
        APP.ModalWindow.onCloseModal();
    }

    componentDidMount () {
    }

    selectWorkspace(wsId) {
        this.setState({
            selectedWs: wsId
        })
    }

    getWorkspacesList() {
        let self = this;
        if (this.props.workspaces) {
            return this.props.workspaces.map(function (ws, i) {
                let selectedClass = (self.state.selectedWs == ws.get('id')) ? 'active' : '';
                return <div className={"item " + selectedClass}
                            key={'ws' + ws.get('id')}
                            onClick={self.selectWorkspace.bind(self, ws.get('id'))}>
                            <div className="content" >
                                {ws.get('name')}
                            </div>
                        </div>;
            });

        } else {
            return '';
        }
    }

    render() {
        let workspacesList = this.getWorkspacesList();
        return <div className="change-workspace-modal">
            <div className="matecat-modal-top">
                <div className="ui one column grid left aligned">
                    <div className="column">
                        <h3>Send this project:</h3>
                        <div className="ui label">
                            <span className="project-id">929830</span> (archived)
                        </div>
                        <span className="project-name">NOME_PROGETTO.TXT</span>
                    </div>
                </div>
            </div>
            <div className="matecat-modal-middle">
                <div className="ui one column grid left aligned">
                    <div className="column">
                        <h3>Choose new Workspace</h3>
                        <div className="column">
                            <div className="ui middle aligned selection divided list">
                                {workspacesList}
                            </div>
                        </div>
                    </div>
                    <div className="column right aligned">
                        <div className="column">
                            <button className="ui button blue right aligned"
                            onClick={this.changeWorkspace.bind(this)}>Move</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>;
    }
}


export default ChangeProjectWorkspace ;
