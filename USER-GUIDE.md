## How to install

1. Backup. Backup your Magento code, database before installing.
3. Upload this package to Magento 2 root/app/code directory
4. Run command line:

```
php bin/magento module:enable Wunderman_CustomerImport
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

## How to Use

1. The import file should be uploaded under the media directory. "pub/media/sample.csv"
2. Run the below command: (Happy Scenario)
	```
		php bin/magento customer:import sample-csv sample.csv
	```
3. Reindex the customer grid using the below command.
	```
		php bin/magento indexer:reindex customer_grid
	```
	
## Notes

1. This extention built and tested on 
	1.1. Magento ver. 2.4.2-p1
	1.2. PHP 7.4.20
	1.3. MYSQL 5.7.34 - MySQL Community Server (GPL)
	1.4. Nginx 1.18.0
	
2. Run the below command for the unhappy scenario will return an error message which is "Selected Profile is not supported, supported profiles [sample-csv]"
	```
		php bin/magento customer:import sample-json sample.json
	```		

