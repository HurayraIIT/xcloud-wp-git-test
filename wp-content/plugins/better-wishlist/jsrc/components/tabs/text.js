const { __ } = wp.i18n;

const {
	TabPanel,
	BaseControl,
	Button,
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
	ColorPicker,
	SelectControl,
} = wp.components;

const { Component, Fragment } = wp.element;

import Color from "./../controls/color";

class CustomTextSettings extends Component {
	constructor() {
		super(...arguments);
	}

	updateValue(value) {
		this.props.onChange(value);
	}

	render() {
		const { state } = this.props;
		return (
			<Fragment>
				<BaseControl
					id="add-to-wishlist-text"
					label="Add to wishlist"
					help="Change button text">
					<TextControl
						value={this.htmlDecode(state.add_to_wishlist_text)}
						onChange={(value) =>
							this.updateValue({
								add_to_wishlist_text: value,
							})
						}
					/>
				</BaseControl>

				<BaseControl
					id="added-to-wishlist-text"
					label="Added to wishlist"
					help="Change button text">
					<TextControl
						value={this.htmlDecode(state.added_to_wishlist_text)}
						onChange={(value) =>
							this.updateValue({
								added_to_wishlist_text: value,
							})
						}
					/>
				</BaseControl>

				<BaseControl
					id="add-to-cart-text"
					label="Add to cart"
					help="Change button text">
					<TextControl
						value={this.htmlDecode(state.add_to_cart_text)}
						onChange={(value) =>
							this.updateValue({
								add_to_cart_text: value,
							})
						}
					/>
				</BaseControl>

				<BaseControl
					id="add-all-to-cart-text"
					label="Add all to cart"
					help="Change button text">
					<TextControl
						value={this.htmlDecode(state.add_all_to_cart_text)}
						onChange={(value) =>
							this.updateValue({
								add_all_to_cart_text: value,
							})
						}
					/>
				</BaseControl>

				<BaseControl
					id="no-product-added-text"
					label="No Product Added"
					help="Change button text">
					<TextControl
						value={this.htmlDecode(state.no_product_added_text)}
						onChange={(value) =>
							this.updateValue({
								no_product_added_text: value,
							})
						}
					/>
				</BaseControl>

			</Fragment>
		);
	}
	htmlDecode(text){
		return  (new DOMParser().parseFromString(text, "text/html")).documentElement.textContent;
	}
}

export default CustomTextSettings;
