/**
 * WordPress dependencies
 */

const { CheckboxControl } = wp.components;
const { Component, createElement } = wp.element;

class AutoSchedule extends Component {
	constructor(props) {
		super(props);
		this.handleChange = this.handleChange.bind(this);
	}

	handleChange( value ) {
		if(value){
			this.props.setIsChecked(!this.props.isChecked);
		}
		else {
			this.props.setIsChecked(false);
		}

		if( this.props.isChecked ) {
			value = '{ "date" : "'+ this.props.post.date +'", "date_gmt" : "'+ this.props.post.date_gmt +'", "status" : "'+ this.props.post.status +'" }';
		}
		this.props.editPost( JSON.stringify(value) );
	}

	componentWillUnmount() {
		this.props.editPost( );
	}

	render() {
		return (
			<React.Fragment>
				<CheckboxControl
					heading = { this.props.label }
					label = { this.props.options.label }
					checked={ this.props.isChecked }
					onChange={ () => { this.handleChange( this.props.options ) } }
				/>
			</React.Fragment>
		);
	}
}

export default AutoSchedule;