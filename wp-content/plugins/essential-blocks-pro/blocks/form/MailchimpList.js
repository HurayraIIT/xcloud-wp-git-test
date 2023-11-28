/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { useEffect, useState, useRef } from "@wordpress/element";
import {
	TextControl,
	PanelRow,
	SelectControl
} from "@wordpress/components";


const { fetchEBSettingsData } = EBControls;

const MailchimpList = (props) => {
	const {
		attributes,
		setAttributes
	} = props

	const {
		integrations
	} = attributes

	const [mailChimpList, setMailchimpList] = useState([])
	const [mailChimpError, setMailchimpError] = useState('')
	const [mailChimpAPIMissing, setMailChimpAPIMissing] = useState(false)

	useEffect(() => {
		const data = new FormData(); //Blank data
		data.append("action", "get_mailchimp_list");
		data.append("admin_nonce", EssentialBlocksProLocalize?.admin_nonce);

		fetch(EssentialBlocksProLocalize.ajax_url, {
			method: "POST",
			body: data,
		}) // wrapped
			.then((res) => res.text())
			.then((data) => {
				const res = JSON.parse(data);
				if (typeof res === 'object') {
					if (!res.data) {
						return;
					}
					if (res.success && typeof res.data === 'object' && Object.keys(res.data).length > 0) {
						const list = []
						Object.keys(res.data).map((item) => {
							list.push({ value: item, label: res.data[item] })
						})
						setMailchimpList(list)
						setMailchimpError('')
						setMailChimpAPIMissing(false)
					}
					else if (!res.success && typeof res.data === 'string') {
						if (res.data === 'api') {
							setMailChimpAPIMissing(true)
						}
						else if (res.data === 'list') {
							setMailchimpError('No Mailchimp List Found!')
						}
					}

				}
				else {
					setMailchimpError('Invalid Response! Please Check you Mailchimp Settings.')
				}
			})
			.catch((err) => console.log(err));
	}, [])

	return (
		<>
			{mailChimpAPIMissing && (
				<PanelRow>
					<div className="eb-instruction">
						Whoops! Seems like MailChimp API Key is Missing.
						To add API key, please go to the settings <a target="_blank" href={EssentialBlocksLocalize?.eb_admin_url + 'admin.php?page=essential-blocks&tab=options'}>here</a>.
					</div>
				</PanelRow>
			)}
			{mailChimpError.length > 0 && (
				<PanelRow>
					<div className="eb-instruction">
						Whoops! Seems like there is no MailChimp List.
					</div>
				</PanelRow>
			)}
			{mailChimpList && mailChimpList.length > 0 && (
				<>
					<SelectControl
						label={__("MailChimp List", "essential-blocks-pro")}
						value={integrations?.mailchimp?.listId}
						options={mailChimpList}
						onChange={(selected) => setAttributes({
							integrations: {
								...integrations,
								mailchimp: { listId: selected }
							}
						})}
					/>

					<PanelRow>
						<div className="eb-instruction">
							MailChimp will only collect data from fields where field name is one of<br />
							<strong>email (required)</strong> | <strong>first-name</strong> | <strong>last-name</strong>
						</div>
					</PanelRow>
					{mailChimpError && (
						<PanelRow><span className="error">{mailChimpError}</span></PanelRow>
					)}
				</>
			)}
		</>
	)
}

export default MailchimpList
