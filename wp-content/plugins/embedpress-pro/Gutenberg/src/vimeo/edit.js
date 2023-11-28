/**
 * Internal dependencies
 */
import EmbedControls from "../common/embed-controls";
import EmbedLoading from "../common/embed-loading";
import EmbedPlaceholder from "../common/embed-placeholder";
import Iframe from "../common/Iframe";

/**
 * WordPress dependencies
 */
const {__} = wp.i18n;
const {Component, Fragment} = wp.element;
import {vimeoIcon} from '../common/icons';
const {Disabled} = wp.components;
class VimeoEdit extends Component {
	constructor() {
		super(...arguments);
		this.switchBackToURLInput = this.switchBackToURLInput.bind(this);
		this.setUrl = this.setUrl.bind(this);
		this.onLoad = this.onLoad.bind(this);
		this.state = {
			editingURL: false,
			url: this.props.attributes.url,
			fetching: true,
			cannotEmbed: false
		};
	}

	componentWillMount() {
		if (this.state.url) {
			this.setUrl();
		}
	}

	onLoad() {
		this.setState({
			fetching: false
		});
	}

	decodeHTMLEntities(str) {
		if (str && typeof str === "string") {
			// strip script/html tags
			str = str.replace(/<script[^>]*>([\S\s]*?)<\/script>/gim, "");
			str = str.replace(/<\/?\w(?:[^"'>]|"[^"]*"|'[^']*')*>/gim, "");
		}
		return str;
	}

	setUrl(event) {
		if (event) {
			event.preventDefault();
		}
		const {url} = this.state;
		const {setAttributes} = this.props;
		setAttributes({url});
		if (
			url &&
			url.match(/^http[s]?:\/\/(?:www\.)?vimeo\.com\/([0-9]{5,12})/i)
		) {
			let mediaIdMatches = url.match(
				/^http[s]?:\/\/(?:www\.)?vimeo\.com\/([0-9]{5,12})/i
			);
			let mediaId = mediaIdMatches[1];
			let iframeSrc = "https://player.vimeo.com/video/" + mediaId;
			let iframeUrl = new URL(iframeSrc);

			// If your expected result is "http://foo.bar/?x=42&y=2"
			for (var key in embedpressProObj.vimeoParams) {
				iframeUrl.searchParams.set(key, embedpressProObj.vimeoParams[key]);
			}
			console.log(iframeUrl);
			this.setState({editingURL: false, cannotEmbed: false});
			setAttributes({iframeSrc: iframeUrl.href});
		} else {
			this.setState({
				cannotEmbed: true,
				editingURL: true
			});
		}
	}

	switchBackToURLInput() {
		this.setState({editingURL: true});
	}

	render() {
		const {url, editingURL, fetching, cannotEmbed} = this.state;
		const {iframeSrc, attrs} = this.props.attributes;
		console.log(iframeSrc);
		const label = __("Vimeo URL");
		// No preview, or we can't embed the current URL, or we've clicked the edit button.
		if (!iframeSrc || editingURL) {
			return (
				<EmbedPlaceholder
					label={label}
					onSubmit={this.setUrl}
					value={url}
					cannotEmbed={cannotEmbed}
					onChange={event => this.setState({url: event.target.value})}
					icon={vimeoIcon}
					DocTitle={__('Learn more about Vimeo')}
					docLink={'https://embedpress.com/docs/embed-vimeo-videos-wordpress/'}
				/>
			);
		} else {
			return (
				<Fragment>
					{fetching ? <EmbedLoading/> : null}
					<Disabled>
						<Iframe
							src={iframeSrc}
							{...attrs}
							onLoad={this.onLoad}
							style={{display: fetching ? "none" : ""}}
							width="640"
							height="360"
						/>
					</Disabled>

					<EmbedControls
						showEditButton={iframeSrc && !cannotEmbed}
						switchBackToURLInput={this.switchBackToURLInput}
					/>
				</Fragment>
			);
		}
	}
}

export default VimeoEdit;
