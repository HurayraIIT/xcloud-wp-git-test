const { __ } = wp.i18n;
const { Component, Fragment } = wp.element;
const { BaseControl, ToggleControl, Dashicon, TextControl, SelectControl, Icon } = wp.components;

import Select from "./../controls/select";

class GeneralSettings extends Component {
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

		return (
			<Fragment>
				<CustomBaseControl
					id="wishlist-page"
					label="Wishlist page"
					help={__(
						"Pick a page as the main Wishlist page. Make sure you add the <b>[better_wishlist]</b> shortcode into the page content."
					)}
				>
					<Select
						value={state.wishlist_page}
						onChange={(newValue) =>
							this.updateValue({
								wishlist_page: newValue,
							})
						}
					/>
				</CustomBaseControl>

				<BaseControl
                    id="wishlist-require-login"
                    label="Require Login"
					help="Enable this option to allow only logged-in users"
                >
                    <ToggleControl
                        checked={ state.wishlist_require_login == "yes" }
                        onChange={ () => {
                            this.updateValue( {
                                wishlist_require_login: state.wishlist_require_login == "yes" ? "no" : "yes",
                            } )
                        } }
                    />
                </BaseControl>

				<BaseControl
					id="wishlist-menu"
					label="Wishlist menu"
					help={__("Add wishlist menu in 'my account' panel.")}
				>
					<ToggleControl
						checked={state.wishlist_menu == "yes"}
						onChange={() =>
							this.updateValue({
								wishlist_menu: state.wishlist_menu == "yes" ? "no" : "yes",
							})
						}
					/>
				</BaseControl>

				<BaseControl
					id="redirect-to-wishlist"
					label="Redirect to wishlist"
					help="Redirect to wishlist page after adding a product to wishlist."
				>
					<ToggleControl
						checked={state.redirect_to_wishlist == "yes"}
						onChange={() =>
							this.updateValue({
								redirect_to_wishlist:
									state.redirect_to_wishlist == "yes" ? "no" : "yes",
							})
						}
					/>
				</BaseControl>

				<BaseControl
					id="redirect-to-cart"
					label="Redirect to cart"
					help="Redirect to cart page after adding a product to cart."
				>
					<ToggleControl
						checked={state.redirect_to_cart == "yes"}
						onChange={() =>
							this.updateValue({
								redirect_to_cart:
									state.redirect_to_cart == "yes" ? "no" : "yes",
							})
						}
					/>
				</BaseControl>

				<BaseControl
					id="remove-from-wishlist"
					label="Remove from wishlist"
					help="Remove from wishlist after adding a product to cart."
				>
					<ToggleControl
						checked={state.remove_from_wishlist == 'yes'}
						onChange={() =>
							this.updateValue({
								remove_from_wishlist: state.remove_from_wishlist  == "yes" ? "no" : "yes",
							})
						}
					/>
				</BaseControl>

				<BaseControl
					id="wishlist-icon-controll"
					label="Show Wishlist Icon"
					help="Enable if you want to show icon">
					<ToggleControl
						checked={state.wishlist_icon_controll == "yes"}
						onChange={() =>
							this.updateValue({
								wishlist_icon_controll:
									state.wishlist_icon_controll == "yes" ? "no" : "yes",
							})
						}
					/>
				</BaseControl>

				{state.wishlist_icon_controll == "yes" && (
					<BaseControl
					id="wishlist-icon"
					label="Add Wishlist Icon"
					help="Change wishlist icon">

					<SelectControl
						value={ state.wishlist_icon }
						onChange={(newValue) => {
							this.updateValue({
								wishlist_icon: newValue,
							});
						}}
						options={ [
							{ label: 'Icon Heart', value: 'icon-heart-stroke' },
							{ label: 'Icon Happy', value: 'icon-happy' },
							{ label: 'Icon Smile', value: 'icon-smile' },
							{ label: 'Icon Tongue', value: 'icon-tongue' },
							{ label: 'Icon Sad', value: 'icon-sad' },
							{ label: 'Icon Wink', value: 'icon-wink' },
							{ label: 'Icon Grin', value: 'icon-grin' },
							{ label: 'Icon Cool', value: 'icon-cool' },
							{ label: 'Icon Angry', value: 'icon-angry' },
							{ label: 'Icon Evil', value: 'icon-evil' },
							{ label: 'Icon Shocked', value: 'icon-shocked' },
							{ label: 'Icon Baffled', value: 'icon-baffled' },
							{ label: 'Icon Confused', value: 'icon-confused' },
							{ label: 'Icon Neutral', value: 'icon-neutral' },
							{ label: 'Icon Hipster', value: 'icon-hipster' },
							{ label: 'Icon Wondering', value: 'icon-wondering' },
							{ label: 'Icon Sleepy', value: 'icon-sleepy' },
						] }
					/>
				</BaseControl>
				)}

				{state.wishlist_icon_controll == "yes" && (
					<BaseControl
					id="added-wishlist-icon"
					label="Added Wishlist Icon"
					help="Change wishlist icon">

					<SelectControl
						value={ state.added_wishlist_icon }
						onChange={(newValue) => {
							this.updateValue({
								added_wishlist_icon: newValue,
							});
						}}
						options={ [
							{ label: 'Icon Heart', value: 'icon-heart-fill' },
							{ label: 'Icon Happy', value: 'icon-happy2' },
							{ label: 'Icon Smile', value: 'icon-smile2' },
							{ label: 'Icon Tongue', value: 'icon-tongue2' },
							{ label: 'Icon Sad', value: 'icon-sad2' },
							{ label: 'Icon Wink', value: 'icon-wink2' },
							{ label: 'Icon Grin', value: 'icon-grin2' },
							{ label: 'Icon Cool', value: 'icon-cool2' },
							{ label: 'Icon Angry', value: 'icon-angry2' },
							{ label: 'Icon Evil', value: 'icon-evil2' },
							{ label: 'Icon Shocked', value: 'icon-shocked2' },
							{ label: 'Icon Baffled', value: 'icon-baffled2' },
							{ label: 'Icon Confused', value: 'icon-confused2' },
							{ label: 'Icon Neutral', value: 'icon-neutral2' },
							{ label: 'Icon Hipster', value: 'icon-hipster2' },
							{ label: 'Icon Wondering', value: 'icon-wondering2' },
							{ label: 'Icon Sleepy', value: 'icon-sleepy2' },
						] }
					/>
				</BaseControl>
				)}
			</Fragment>
		);
	}
}

export default GeneralSettings;
