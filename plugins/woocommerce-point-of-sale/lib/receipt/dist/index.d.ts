import { ReceiptType, ReceiptOptions, ReceiptData, ReceiptI18n, ReceiptDataAddress } from './types';
import { LitElement } from 'lit';
/**
 * Generates receipt from cart, order or preview.
 */
export declare class AppReceipt extends LitElement {
    type: ReceiptType;
    data: ReceiptData;
    options: ReceiptOptions;
    i18n: ReceiptI18n;
    constructor();
    getI18n(id: number): string;
    getReceiptOption<K extends keyof ReceiptOptions>(key: K): ReceiptOptions[K];
    getReceiptData<K extends keyof ReceiptData>(key: K): ReceiptData[K];
    styleAlign(align?: 'left' | 'right' | 'center'): import("lit-html/directive").DirectiveResult<typeof import("lit-html/directives/style-map").StyleMapDirective>;
    formatAddress(address: Partial<ReceiptDataAddress>): string;
    formatDate(date: string): string;
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'app-receipt': AppReceipt;
    }
}
