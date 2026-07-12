/*
 * racereg - PAY by square QR, client-side init (issue #25).
 *
 * Reads the non-secret payment data emitted by the #24 confirmation page,
 * builds the bysquare PaymentOrder model, encodes it (pure-JS LZMA, no xz) and
 * renders the QR as an SVG into the mount element. Pure browser code: if
 * anything is missing or throws, it returns quietly and the textual payment
 * details from #24 remain as the fallback. Nothing is stored.
 */
import { encode, PaymentOptions } from "bysquare/pay";
import qrcode from "qrcode-generator";

function renderQr() {
	var mount = document.getElementById("racereg-qr-mount");
	var dataEl = document.getElementById("racereg-qr-data");
	if (!mount || !dataEl) { return; }

	var data;
	try {
		data = JSON.parse(dataEl.textContent || dataEl.innerText || "{}");
	} catch (e) {
		console.warn("[racereg-qr]", e); // malformed payload -> keep textual fallback
		return;
	}

	var iban = String(data.iban || "").replace(/\s+/g, "").toUpperCase();
	if (!iban) { return; } // no payee account configured -> textual fallback only

	var account = { iban: iban };
	var bic = String(data.bic || "").replace(/\s+/g, "").toUpperCase();
	if (bic) { account.bic = bic; }

	var payment = {
		type: PaymentOptions.PaymentOrder,
		currencyCode: String(data.currency || "EUR"),
		bankAccounts: [account],
		beneficiary: { name: String(data.beneficiary || "") },
	};

	var amount = parseFloat(data.amount);
	if (!isNaN(amount) && amount > 0) { payment.amount = amount; }

	var vs = String(data.variableSymbol || "");
	if (vs) { payment.variableSymbol = vs; }

	var qrString;
	try {
		// bysquare deburrs diacritics by default (bank compatibility) - leave it.
		qrString = encode({ payments: [payment] });
	} catch (e) {
		console.warn("[racereg-qr]", e); // encode/validation failed -> textual fallback remains
		return;
	}

	var svg;
	try {
		var qr = qrcode(0, "M");
		qr.addData(qrString);
		qr.make();
		svg = qr.createSvgTag({ cellSize: 6, margin: 16, scalable: true });
	} catch (e) {
		console.warn("[racereg-qr]", e);
		return;
	}

	mount.innerHTML = svg;

	// Reveal the (initially hidden) QR block now that it actually rendered.
	var wrap = document.getElementById("racereg-qr");
	if (wrap) { wrap.removeAttribute("hidden"); }
}

if (document.readyState === "loading") {
	document.addEventListener("DOMContentLoaded", renderQr);
} else {
	renderQr();
}
