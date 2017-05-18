# how-to-look
seeks paths through a database of queries related by Google's Knowledge Graph

if MySQLdb isn't installed
http://stackoverflow.com/questions/1448429/how-to-install-mysqldb-python-data-access-library-to-mysql-on-mac-os-x#1448476

fill out the config.py file with your database info

switch to the backend directory in Terminal. Use it like this:

for help:
python seek_out_from_x.py --help

to seek outward from one person (this will run indefinitely until it's sought to a specific depth (default 5))
python seek_out_from_x.py 'vito acconci' 

change the depth
python seek_out_from_x.py 'vito acconci' --depth 10

to find paths from the source to the target
python seek_out_from_x.py 'vito acconci' --source 'alex jones' --verbose

some of the seeking infomation is cached in the DB, so the program can be stopped and restarted without losing its place. To overcome this, use the options:
--reset (resets all data for seeking to source, removes path info to source from DB)
--reset_hard (resets all data, wipes ALL path info from DB. Use with caution!)
