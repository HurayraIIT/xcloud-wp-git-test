const { __ } = wp.i18n;
const { Component, Fragment } = wp.element;
const { BaseControl, TextControl, CheckboxControl } = wp.components;

class WishlistPage extends Component {
    constructor() {
        super(...arguments);
    }

    updateValue(value) {
		this.props.onChange(value);
	}

    render() {
        const { state } = this.props;

        return(
            <Fragment>
                <BaseControl
					id="wishlist-table-heading-show"
					label="Show Row in Wishlist page"
					help={__(
						"Click field name which field you want to show in wishlist page"
					)}
				>
                    <ul>
                        <li>
                            <CheckboxControl
                                label="Image"
                                checked={ state.wishlist_table_image == "yes" }
                                onChange={ () => {
                                    this.updateValue( {
                                        wishlist_table_image: state.wishlist_table_image == "yes" ? "no" : "yes",
                                    } )
                                } }
                            />
                        </li>
                        <li>
                            <CheckboxControl
                                label="Product Name"
                                checked={ state.wishlist_table_product_name == "yes" }
                                onChange={ () => {
                                    this.updateValue( {
                                        wishlist_table_product_name: state.wishlist_table_product_name == "yes" ? "no" : "yes",
                                    } )
                                } }
                            />
                        </li>
                        <li>
                            <CheckboxControl
                                label="Stock Status"
                                checked={ state.wishlist_table_stock == "yes" }
                                onChange={ () => {
                                    this.updateValue( {
                                        wishlist_table_stock: state.wishlist_table_stock == "yes" ? "no" : "yes",
                                    } )
                                } }
                            />
                        </li>
                        <li>
                            <CheckboxControl
                                label="Price"
                                checked={ state.wishlist_table_price == "yes" }
                                onChange={ () => {
                                    this.updateValue( {
                                        wishlist_table_price: state.wishlist_table_price == "yes" ? "no" : "yes",
                                    } )
                                } }
                            />
                        </li>
                        <li>
                            <CheckboxControl
                                label="Action"
                                checked={ state.wishlist_table_add_to_cart == "yes" }
                                onChange={ () => {
                                    this.updateValue( {
                                        wishlist_table_add_to_cart: state.wishlist_table_add_to_cart == "yes" ? "no" : "yes",
                                    } )
                                } }
                            />
                        </li>
                    </ul>
				</BaseControl>

                <BaseControl
                    id="wishlist-table-heading"
                    label="wishlist table heading"
                    help={__("Can change wishlist table heading text")}
                >
                    <ul>
                        <li>
                        <TextControl
                                id="image-text"
                                label="Image"
                                value={this.htmlDecode(state.wishlist_table_image_text)}
                                onChange={(value) =>
                                    this.updateValue({
                                        wishlist_table_image_text:value,
                                    })
                                }
                            />
                        </li>
                        <li>
                            <TextControl
                                label="Product Name"
                                value={this.htmlDecode(state.wishlist_table_name_text)}
                                onChange={(value) =>
                                    this.updateValue({
                                        wishlist_table_name_text:value,
                                    })
                                }
                            />
                        </li>
                        <li>
                            <TextControl
                                id="stock-status"
                                label="Stock Status"
                                value={this.htmlDecode(state.wishlist_table_stock_text)}
                                onChange={(value) =>
                                    this.updateValue({
                                        wishlist_table_stock_text: value
                                    })
                                }
                            />
                        </li>
                        <li>
                            <TextControl
                                label="Price"
                                value={this.htmlDecode(state.wishlist_table_price_text)}
                                onChange={(value) =>
                                    this.updateValue({
                                        wishlist_table_price_text: value
                                    })
                                }
                            />
                        </li>
                        <li>
                            <TextControl
                                label="Action"
                                value={this.htmlDecode(state.wishlist_table_add_to_cart_text)}
                                onChange={(value) =>
                                    this.updateValue({
                                        wishlist_table_add_to_cart_text: value
                                    })
                                }
                            />
                        </li>
                    </ul>
                </BaseControl>

                <BaseControl
                    id="image-size"
                    label="Image Size"
                    help={__("You can change wishlist page image size")}
                >
                    <ul>
                        <li>
                            <TextControl
                                label="Width"
                                value={this.htmlDecode(state.wishlist_table_image_width)}
                                onChange={(value) =>
                                    this.updateValue({
                                        wishlist_table_image_width: value
                                    })
                                }
                            />
                        </li>
                    </ul>
                </BaseControl>
            </Fragment>
        );
    }

    htmlDecode(text){
		return  (new DOMParser().parseFromString(text, "text/html")).documentElement.textContent;
	}
}

export default WishlistPage;