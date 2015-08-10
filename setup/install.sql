CREATE TABLE IF NOT EXISTS `PREFIX_shopgate_order`
		(
			`id_shopgate_order` int(11) NOT NULL AUTO_INCREMENT,
			`id_cart` int(11) NOT NULL DEFAULT '0',
			`id_order` int(11) DEFAULT NULL,
			`order_number` int(16) NOT NULL,
			`shop_number` int(16) NULL DEFAULT NULL,
			`tracking_number` varchar(32) NOT NULL DEFAULT '',
			`shipping_service` varchar(16) NOT NULL DEFAULT 'OTHER',
			`shipping_cost` decimal(17,2) NOT NULL DEFAULT '0.00',
			`comments` text NULL DEFAULT NULL,
			`status` int(1) NOT NULL DEFAULT '0',
			`shopgate_order` text NULL DEFAULT NULL,
			PRIMARY KEY (`id_shopgate_order`),
			UNIQUE KEY `order_number` (`order_number`)
		)
		ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_shopgate_customer`
  (
	`id_shopgate_customer` int(11) NOT NULL AUTO_INCREMENT,
	`id_customer` int(11) NOT NULL DEFAULT '0',
	`customer_token` varchar(255) NOT NULL DEFAULT '0',
	`date_add` datetime NOT NULL,
	PRIMARY KEY (`id_shopgate_customer`),
	UNIQUE KEY `id_customer_token` (`customer_token`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
