linnworks.net

App Doc:
http://www.linnworks.com/support
http://www.linnworks.com/training-videos
http://apps.linnworks.net/Api





prior file changes on oscommerce system: to duplicate sku from products.products_id into products.products_model, move products_model into products_description.products_description

/admin/ms.php
/admin/categories.php
/admin/includes/languages/categories.php
/admin/includes/languages/invoice.php
/admin/includes/languages/batch_print.php
/admin/includes/languages/orders.php
/admin/includes/languages/stats_sales.php
/admin/includes/languages/products_variants.php
/admin/includes/languages/stats_haircare_orders.php
/admin/includes/languages/xsell.php
/admin/includes/languages/stats_low_stock_attrib.php
/admin/includes/modules/batch_print/templates/invoice.php
/admin/includes/javascript/bundleproducts/categories_ajax1.php
/admin/includes/javascript/products_variants/products_variants_manager.php
/admin/includes/javascript/stock_admin/stock_admin.php
/admin/includes/javascript/orders_returns/rm.php
/admin/includes/javascript/suppliers/categories_ajax1.php
/admin/includes/classes/orders.php
/admin/includes/functions/suppliers_orders.php
/admin/stockadmin.php
/admin/stock.php
/admin/suppliers_admin.php
/admin/xsell.php

/checkout_process.php
/styles.css
/product_info.php
/checkout.php
/shopping_cart.php
/includes/classes/shopping_cart.php
/includes/functions/general_added.php


- Currently linnworks.net Fails to connect to UPS
- Interlink Express refer to [dpd local]


oscommerce integration with linnworks can only be done with desktop app
website integration: Oscommerce can only be done within linnwork desktop
http://www.linnworks.com/Doc/Website_Integration_Gateway

FTP Server path – ftp://**.***.***.***/httpdocs/
FTP Username – []
FTP Password – []
Site URL – []
Database Server - localhost
Database name – []
Database username – []
Database password – []


Initial Rule Engine quick notes:
===================================================================
MTM : International Business Mail Signed Large Letter
MP5 : International Business Parcels Signed
MTA : International Business Parcels Tracked and Signed
MTI : International Business Mail Tracked Large Letter
MP1 : International Business Parcels Tracked
MTC : International Business Mail Tracked and Signed Large Letter

default:
  parcel -> INTERSIGN MP5
  large letter -> MTM

$tracked_countries & parcel -> MP1
$tracked_countries & lg letter -> MTI

$tracked_and_signed_countries & parcel -> MTA
$tracked_and_signed_countries & lg letter -> MTC

Tracked:
Belgium|Canada|Denmark|Finland|France|Germany|Hong Kong|Hungary|Ireland|Malta|Netherlands|New Zealand|Poland|Portugal|Sweden|Switzerland|United States|Singapore|Japan

Track&Signed
Cyprus|Czech Republic|Greece|Italy|Liechtenstein|Romania|Slovenia|Gibraltar

Signed (Others):
Doesn't match any of the above

If Tracked24/Track48 -> if order total -> > Tracked24 with signature/Track48 with signature


Generic Import: http://www.linnworks.com/support/settings/import-and-export-data/import-data/import-types/inventory-import
    SKU
    Barcode Number:
    Primary Image:
    Image URL:
    Source: OSCOMMERCE
    SubSource: oscommerce
    Location: Default
    Weight: 
    Minimum Level: 0 
    Stock Level: 
    Stock Value: 
    Title: 
    Title by Channel: 
    Price by Channel:
    Description by Channel:
    Retail Price:
    Purchase Price:

    Is Variation Group: ('Yes' for parent, 'No' for non-parent)
    Variation Group Name: (parent title for parent, empty for non-parent)
    Variation SKU: (parent sku)

    Item extended property: Model/Brand/Colour Group/Colour/Length/Size/etc..
    Property Type: Attribute

    for parent sku all column blank except: SKU, Is Variation Group, VariationSKU, Variation Group Name

Composites/Bundles Import: http://www.linnworks.com/support/settings/import-and-export-data/import-and-export-data-how-to-guides/how-to-import-composites
    Parent SKU: bundle_id
    Child SKU: subproduct_id
    Quantity: subproduct_qty

Import images in bulk: http://www.linnworks.com/support/settings/import-and-export-data/import-and-export-data-how-to-guides/how-to-import-images-in-bulk-into-linnworks
    Normally, a Linnworks field cannot be mapped to more than one column in a CSV file. However, Image URL is an exception to this and can be mapped to multiple file columns

Channel specific price, title and description: http://www.linnworks.com/support/settings/import-and-export-data/import-and-export-data-how-to-guides/kb-how-to-import-a-channel-specific-price-title-and-description
    SKU
    Title by Ebay
    Price by Ebay
    Description by Ebay
    Title by Amazon
    Price by Amazon
    Description by Amazon

Channel Stock&Price:
    SKU
    Stock Level
    Price by Channel
    Location
    

Import Extended Item Properties: http://www.linnworks.com/support/settings/import-and-export-data/import-and-export-data-how-to-guides/how-to-import-extended-item-properties


Others: http://www.linnworks.com/support/settings/import-and-export-data/import-and-export-data-how-to-guides


Update Supplier Details for Linnworks SKUs in Bulk: http://www.linnworks.com/support/settings/import-and-export-data/import-and-export-data-how-to-guides/adding-and-updating-supplier-details-to-linnworks




export file function:

/admin/stats_om.php
/admin/includes/classes/stats_om.php

download inventory and composites from /admin/stats_om.php upload them

Then after they are uploaded successfully

Map The inventory to linnworks:

http://www.linnworks.com/support/settings/channel-integration/inventory-mapping


open linnworks desktop -> setting -> channels -> Auto Sync Setting -> enable Inventory Sync


http://www.linnworks.com/support/order-book/open-order
http://www.linnworks.com/support/order-book/processed-orders



comment
    cancelled orders are in processed order, as long as we search the order details
    always cancel shipping label before deleting/canceling any orders


process order import
    http://www.linnworks.com/support/settings/import-and-export-data/import-data/import-types/process-orders-import
Open Orders Export
    http://www.linnworks.com/support/settings/import-and-export-data/export-data/export-types/open-orders-export
    








Email Notification:
http://www.linnworks.com/support/emails/email-notifications



Royal Mail manifests need to be filed daily (except weekends) as otherwise you will not be able to  print labels.

Interlink consignments do not need to be manifested, as all the printed labels appear in your Interlink account instantly. Manifest option in Linnworks, is implemented for your own reference, so you could track down the number of parcels processed daily.

If you need a printed list of Interlink consignments, please use 'Print Consignments' option. File layout can be edited in Settings > Template Designer > Consignments.


processed order reprinted
    same label for processed orders (e.g. damaged label):
        processed order screen. right click -> action -> reprint label
    extra label for processed orders:
        right click > action > Returns, Exchanging & Resends > New > Resend > enter qty > OK (This will request a new tracing number from your shipping vendor. All the details will stay the same (if you don`t want to customise it) only tracking number will be different. )


Email template example:

    condition: [{Source}] = "OSCOMMERCE" and [{SubSource}] = "oscommerce" ([{Source}] = "OSCOMMERCE" and [{SubSource}] = "oscommerce" and [{Processed}] doesn't work, remove [{Processed}], And then once you process an order from OSCOMMERCE , then go to that order's audit trail in processed order screen and there you will see that email was sent. Then you will also find that email in the Sent Mail, When you open any order for details > Click on the Audit Trail tab)
    Email body:

        Dear Customer,

        Order ID: [{SecondaryReference}]

        Order Date: [{ReceivedDate}]

        Your order is despatched with [{ShippingMethod}]. It can be tracked at: [{EVAL}]IIF[[{ShippingVendor}]="ROYALMAILTRACKED","https://www.royalmail.com/track-your-item",""][{ENDEVAL}]

        [{EVAL}]IIF[[{ShippingVendor}]="ROYALMAILOBA","https://www.royalmail.com/track-your-item",""][{ENDEVAL}]

        [{EVAL}]IIF[[{ShippingVendor}]="INTERLINK","http://www.dpdlocal.co.uk/",""][{ENDEVAL}].

        Tracking number:[{PostalTrackingNumber}].

        Please do not hesitate to contact us if you have any question.

    In email notification template it will not work with a tag being inside the hyperlink in the email body


    
    
    
    
    
ebay listing download:
http://www.fittedcommerce.com/how-to-download-my-ebay-listings/
