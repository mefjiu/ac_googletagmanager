<!-- Google Tag Manager Data Layer -->
<script>
window.dataLayer = window.dataLayer || [];

var dataProducts = [];
{foreach from=$products_cart item=product}
dataProducts.push({
    'sku' : '{$product['id_product']}',
    'name' : '{$product['name']}',
    'category' : '{$product['category']}',
    'price' : '{$product['price_wt']}',
    'quantity' : '{$product['quantity']}'
});
{/foreach}

dataLayer.push({
    'ecommerce' : {
        'purchase' : {
            'actionField' : {
                'id': '{$order->id}',
                'revenue' : '{$order->total_paid}',
                'shipping': '{$order->total_shipping}',
        	},
        	'products' : dataProducts
        }
	}
});
</script>
<!-- End Google Tag Manager Data Layer -->