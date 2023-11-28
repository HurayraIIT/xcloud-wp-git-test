/**
 * WordPress dependencies
 */

const { SelectControl } = wp.components
const { Component, createElement } = wp.element

class ScheduleList extends Component {
    constructor(props) {
        super(props)
        this.handleChange = this.handleChange.bind(this)
    }

    handleChange(value) {
        this.props.editPost(value)
        this.props.setManualSchedule(value);
    }

	componentWillUnmount() {
		this.props.editPost( );
	}

    render() {
        return (
            <SelectControl
                label={this.props.label}
                options={this.props.options}
                value={this.props.manualSchedule}
                onChange={(value) => {
                    this.handleChange(value)
                }}
            />
        )
    }
}
export default ScheduleList
