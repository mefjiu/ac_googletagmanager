<!-- Google Tag Manager Data Layer -->
<script>
window.dataLayer = window.dataLayer || [];

var dataProducts = [];
{foreach from=$products_cart item=product}
dataProducts.push({
    'name' : '{$product['name']}',
    'id' : '{$product['id_product']}',
    'brand' : '{$product['manufacturer_name']}',
    'sku' : '{$product['reference']}',
    'category' : '{$product['category']}',
    'price' : '{$product['price_wt']}',
    'quantity' : '{$product['quantity']}',
    'coupon': ''
});
{/foreach}

dataLayer.push({
    'event': 'Transaction',
    'ecommerce' : {
        'purchase' : {
            'actionField' : {
                'id': '{$order->id}',
                'revenue' : '{$order->total_paid}',
                'tax' : '{$order->total_paid_tax_incl - $order->total_paid_tax_excl}',
                'shipping': '{$order->total_shipping}',
                'coupon': ''
        	},
        	'products' : dataProducts
        }
	}
});
</script>
<!-- End Google Tag Manager Data Layer -->