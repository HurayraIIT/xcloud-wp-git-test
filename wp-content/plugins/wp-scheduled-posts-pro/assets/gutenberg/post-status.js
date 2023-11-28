const { Component, createElement } = wp.element;

class RadioInput extends Component {
	constructor(props) {
		super(props);
        this.state = {
            dontShare: true,
        }
        this.radioOptions = [
            {
                label: 'Draft',
                info: 'The post will remind in draft until publish.',
                value: 'draft'
            },
            {
                label: 'Keep Publish',
                info: 'The updated content will published as per schedule.',
                value: 'publish'
            },
        ];
	}

    componentDidMount() {
        const element = document.getElementById('wpscpprodontshare');
        this.setState({dontShare: element.checked});
        element.addEventListener('change', (event) => {
            this.setState({dontShare: event.target.checked});
        });
   }


	handleChange( value ) {
        this.props.setState(value);
        this.props.setMetaValue({wpsp_status: value});
        if(value == 'publish'){
            const checkbox = document.getElementById('wpscpprodontshare');
            if(checkbox && !checkbox.classList.contains('unchecked-once')){
                if(!checkbox.checked){
                    checkbox.click();
                }
                // checkbox.checked = false;
                checkbox.classList.add("unchecked-once");
            }
        }
	}
	render() {
        if(this.props.status !== 'future') return '';

        return (
            <fieldset
                className={`editor-post-visibility__dialog-fieldset wpsp-schedule-type`} style= {this.props.style}
            >
                <legend className="editor-post-visibility__dialog-legend">
                    Post Status After Schedule
                </legend>
                <div className='wpsp-input-wrapper'>
                    { this.radioOptions.map( ( { value, label, info } ) => (
                        <label
                            key={ value }
                            className="editor-post-visibility__choice"
                        >
                            <input
                                type="radio"
                                name="radio_input"
                                value={ value }
                                onChange={(event) => this.handleChange(value) }
                                checked={ this.props.wpsp_status == value }
                                id={ `editor-post-${ value }` }
                                className="editor-post-visibility__dialog-radio"
                            />
                            <b
                                className="editor-post-visibility__dialog-label"
                            >
                                { label }
                            </b>
                            {
                                info &&
                                <p
                                    className="editor-post-visibility__dialog-info"
                                >
                                    { info }
                                </p>
                            }
                        </label>
                    ) ) }

                    {
                        this.props.wpsp_status == 'publish' &&
                        this.state.dontShare &&
                        (<p id="components-form-token-suggestions-howto-1" className="components-form-token-field__help">Social Share is Disable, Enable it from <a href="#WpScp_instantshare_meta_box">Social Share setting</a> to share your preferred social media.</p>)
                    }
                </div>
            </fieldset>
        );
	}
}
export default RadioInput;