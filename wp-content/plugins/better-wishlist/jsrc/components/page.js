const { __ } = wp.i18n;
const { TabPanel, Button } = wp.components;
const { Component, Fragment } = wp.element;

import Swal from "sweetalert2/dist/sweetalert2.js";
import "sweetalert2/src/sweetalert2.scss";

import GeneralSettings from "./tabs/general";
import ButtonSettings from "./tabs/button";
import CustomTextSettings from "./tabs/text";
import WishlistPage from "./tabs/wishlist-page";
import MiniWishlist from "./tabs/mini-wishlist";
import StyleSettings from "./tabs/style";

import GeneralIcon from "../icons/general.svg";
import ButtonIcon from "../icons/button.svg";
import TextIcon from "../icons/custom-text.svg";
import StyleIcon from "../icons/style.svg";

import Logo from "../../public/assets/images/svg/logo.svg";

class Page extends Component {
	constructor() {
		super(...arguments);

		this.state = {
			wishlist_page: null,
			wishlist_nav: null,
			mini_wishlist_controll: "yes",
			wishlist_menu: "yes",
			redirect_to_wishlist: "no",
			redirect_to_cart: "no",
			remove_from_wishlist: "yes",
			show_in_loop: "yes",
			wishlist_require_login: "no",
			position_in_loop: "after_add_to_cart",
			position_in_single: "after_add_to_cart",
			add_to_wishlist_text: __("Add to wishlist"),
			added_to_wishlist_text: __("Added to wishlist"),
			add_to_cart_text: __("Add to cart"),
			add_all_to_cart_text: __("Add all to cart"),
			no_product_added_text: __("No Product Added"),
			wishlist_icon_controll: 'yes',
			wishlist_table_image: 'yes',
			wishlist_table_product_name: 'yes',
			wishlist_table_stock: 'yes',
			wishlist_table_price: 'yes',
			wishlist_table_add_to_cart: 'yes',
			wishlist_table_image_text: __("Image"),
			wishlist_table_name_text: __("Product Name"),
			wishlist_table_stock_text: __("Stock Status"),
			wishlist_table_price_text: __("Price"),
			wishlist_table_add_to_cart_text: __("Add to Cart"),
			wishlist_table_image_width: 120,
			wishlist_button_style: "default",
			wishlist_button_width: 175,
			wishlist_button_fontsize: 12,
			wishlist_button_font_style: "initial",
			wishlist_button_color: "#ffffff",
			wishlist_button_background: "default",
			wishlist_button_hover_color: "default",
			wishlist_button_hover_background: "default",
			wishlist_button_border_style: "none",
			wishlist_button_border_width: 1,
			wishlist_button_border_color: "default",
			wishlist_button_padding_top: 0,
			wishlist_button_padding_right: 0,
			wishlist_button_padding_bottom: 0,
			wishlist_button_padding_left: 0,
			cart_button_style: "default",
			cart_button_fontsize: 12,
			cart_button_font_style: "initial",
			cart_button_color: "#ffffff",
			cart_button_background: "default",
			cart_button_hover_color: "default",
			cart_button_hover_background: "default",
			cart_button_border_style: "none",
			cart_button_border_width: 1,
			cart_button_border_color: "default",
			cart_button_padding_top: 0,
			cart_button_padding_right: 0,
			cart_button_padding_bottom: 0,
			cart_button_padding_left: 0,
			wishlisted_button_color: "#fff",
			wishlisted_button_hover_color: "#fff",
			wishlisted_button_bg: "#ff0000",
			wishlisted_button_hover_bg: "#ff0000",
		};
	}

	componentDidMount() {
		this.setState({
			...this.state,
			...BetterWishlist.settings,
		});

		// get wp page list
		const { localStorage } = window;

		wp.apiFetch({ path: "/wp/v2/pages?per_page=-1" }).then((pages) => {
			if (pages.length > 0) {
				let opts = [];

				pages.map((page) => {
					opts.push({
						label: page.title.rendered,
						value: page.id,
					});
				});

				localStorage.setItem("bwpOpts", JSON.stringify(opts));
			}
		});

		//Get header location from theme menu setting
		wp.apiFetch({ path: "/wp/v2/menu-locations" }).then((menus) => {
				let opts = [];
				let initialLebel = { label: 'Select Menu', value: null };
				opts.unshift( initialLebel );
				for( let i in menus ){
					opts.push({
						label: menus[i].description,
						value: menus[i].name,
					});
				}
				localStorage.setItem("bwpOptsM", JSON.stringify(opts));
		});
	}

	onChange(newState) {
		this.setState({
			...this.state,
			...newState,
		});
	}

	saveForm(ev) {
		const settings = this.state;
		const thisButton = ev.target;
		ev.preventDefault();

		thisButton.classList.add("saving");

		jQuery.ajax({
			type: "POST",
			url: BetterWishlist.ajaxurl,
			data: {
				action: "bw_save_settings",
				security: BetterWishlist.nonce,
				settings,
			},
			beforeSend: function () {
				thisButton.innerHTML = `<svg id="bw-spinner" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48"><circle cx="24" cy="4" r="4" fill="#fff"/><circle cx="12.19" cy="7.86" r="3.7" fill="#fffbf2"/><circle cx="5.02" cy="17.68" r="3.4" fill="#fef7e4"/><circle cx="5.02" cy="30.32" r="3.1" fill="#fef3d7"/><circle cx="12.19" cy="40.14" r="2.8" fill="#feefc9"/><circle cx="24" cy="44" r="2.5" fill="#feebbc"/><circle cx="35.81" cy="40.14" r="2.2" fill="#fde7af"/><circle cx="42.98" cy="30.32" r="1.9" fill="#fde3a1"/><circle cx="42.98" cy="17.68" r="1.6" fill="#fddf94"/><circle cx="35.81" cy="7.86" r="1.3" fill="#fcdb86"/></svg> &nbsp;<span>${__(
					"Saving Data..",
					"betterwishlist"
				)}</span>`;
			},
			success: function (response) {
				thisButton.innerHTML = __("Save Settings", "better-wishlist");
				setTimeout(function () {
					Swal.fire({
						type: "success",
						icon: "success",
						title: __("Settings Saved!", "better-wishlist"),
						footer: __("Have Fun", "better-wishlist"),
						showConfirmButton: false,
						timer: 2000,
					});
					thisButton.classList.remove("saving");
				}, 500);
			},
			error: function (response) {
				console.log(response);
			},
		});
	}

	render() {
		return (
			<Fragment>
				<div className="bw-settings-header">
					<div className="bw-settings-bar">
					<img src={Logo} />
					</div>
					<Button
						className="save-button"
						onClick={this.saveForm.bind(this)}>
						{__("Save Settings")}
					</Button>
				</div>

				<div className="bw-settings-content">
					<TabPanel
						tabs={[
							{
								name: "general",
								title: __("General"),
								className: "tab-general",
							},
							{
								name: "button",
								title: __("Button"),
								className: "tab-button",
							},
							{
								name: "wishlist-page",
								title: __("Wishlist Page"),
								className: "tab-wishlist-page",
							},
							{
								name: "mini-wishlist",
								title: __("Mini Wishlist"),
								className: "tab-mini-wishlist",
							},
							{
								name: "custom-text",
								title: __("Custom Text"),
								className: "tab-custom-text",
							},
							{
								name: "style",
								title: __("Style"),
								className: "tab-style",
							},
						]}
						initialTabName="general">
						{(tab) => {
							if (tab.name == "general") {
								return (
									<GeneralSettings
										state={this.state}
										onChange={this.onChange.bind(this)}
									/>
								);
							} else if (tab.name == "button") {
								return (
									<ButtonSettings
										state={this.state}
										onChange={this.onChange.bind(this)}
									/>
								);
							}else if (tab.name == "wishlist-page") {
								return (
									<WishlistPage
										state={this.state}
										onChange={this.onChange.bind(this)}
									/>
								);
							}else if (tab.name == "mini-wishlist") {
								return (
									<MiniWishlist
										state={this.state}
										onChange={this.onChange.bind(this)}
									/>
								);
							} else if (tab.name == "custom-text") {
								return (
									<CustomTextSettings
										state={this.state}
										onChange={this.onChange.bind(this)}
									/>
								);
							} else if (tab.name == "style") {
								return (
									<StyleSettings
										state={this.state}
										onChange={this.onChange.bind(this)}
									/>
								);
							}
						}}
					</TabPanel>
				</div>
			</Fragment>
		);
	}
}

export default Page;
