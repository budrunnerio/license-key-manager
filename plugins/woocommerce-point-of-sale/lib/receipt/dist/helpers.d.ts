import type { ReceiptDataAddress, ReceiptDataAddressFormats, ReceiptDataCountry } from './types';
export declare function phpToMomentFormat(format: string): string;
/** @see WC_Countries::get_formatted_address() in WC core. */
export declare function getFormattedAddress(args: Partial<ReceiptDataAddress> | undefined, formats: ReceiptDataAddressFormats, countries: ReceiptDataCountry[], nameFormat?: string, separator?: string): string;
