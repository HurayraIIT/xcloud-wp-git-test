const { __ } = wp.i18n;
const { Component, Fragment } = wp.element;
const { BaseControl, TextControl, CheckboxControl, ToggleControl } = wp.components;

import SelectMenuNav from "./../controls/selectmenu";

class MiniWishlist extends Component {
    constructor() {
        super(...arguments);
    }

    updateValue(value) {
		this.props.onChange(value);
	}

    render() {
        const { state } = this.props;
		const CustomBaseControl = ({ help, ...props }) => (
			<BaseControl
			{ ...props }
			label={props.label}
			help={<div dangerouslySetInnerHTML={{ __html: help }} />}
			/>
		);

        return(
            <Fragment>
                <CustomBaseControl
					id="wishlist-icon-controll"
					label="Show Wishlist Icon"
					help={__(
						"You can add the <b>[better_wishlist_mini]</b> shortcode into the page content."
					)}
                    >
					<ToggleControl
						checked={state.mini_wishlist_controll == "yes"}
						onChange={() =>
							this.updateValue({
								mini_wishlist_controll: state.mini_wishlist_controll == "yes" ? "no" : "yes",
							})
						}
					/>
				</CustomBaseControl>
                {state.mini_wishlist_controll == "yes" && (
                <BaseControl
					id="wishlist-page-nav"
					label="Wishlist page"
					help={__(
						"Select a menu position which show mini wishlist"
					)}
				>
					<SelectMenuNav
						value={ state.wishlist_nav }
						onChange={ ( newValue ) =>
							this.updateValue( {
								wishlist_nav: newValue,
							} )
						}
					/>
				</BaseControl>
                )}
            </Fragment>
        );
    }

    htmlDecode(text){
		return  (new DOMParser().parseFromString(text, "text/html")).documentElement.textContent;
	}
}
export default MiniWishlist;