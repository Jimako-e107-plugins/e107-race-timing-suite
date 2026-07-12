/*
 * racereg - build-time PAY by square encode check (issue #40).
 *
 * Runs during `npm run build` BEFORE esbuild. It imports encode() from the same
 * "bysquare/pay" subpath the browser entry uses and encodes a representative,
 * fully-valid payload (SK IBAN + amount + variable symbol + non-empty
 * beneficiary). If the import path regresses, or encode() returns something
 * other than a non-empty string, the build fails loudly here instead of the QR
 * silently never rendering in the browser.
 */
import { encode, PaymentOptions } from "bysquare/pay";

const payload = {
	payments: [
		{
			type: PaymentOptions.PaymentOrder,
			currencyCode: "EUR",
			amount: 25.0,
			variableSymbol: "1234567890",
			bankAccounts: [{ iban: "SK3112000000198742637541" }],
			beneficiary: { name: "Race Organizer" },
		},
	],
};

let qr;
try {
	qr = encode(payload);
} catch (e) {
	console.error("[racereg-qr] build-time encode() threw:", e);
	process.exit(1);
}

if (typeof qr !== "string" || qr.length === 0) {
	console.error("[racereg-qr] build-time encode() returned an empty/invalid result:", qr);
	process.exit(1);
}

console.log("[racereg-qr] build-time encode() OK (" + qr.length + " chars).");
