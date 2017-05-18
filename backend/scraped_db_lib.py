import MySQLdb as mdb
import sys, json, logging, config

try:
    con = mdb.connect(host=config.mysql['host'], user=config.mysql['user'], passwd=config.mysql['passwd'], db=config.mysql['db'], charset='utf8')
    cur = con.cursor(mdb.cursors.DictCursor)
except mdb.Error, e:
    print "Error %d: %s" % (e.args[0],e.args[1])
    sys.exit(1)


def convert_query_to_key(query):
	if (isinstance(query, int)) or (isinstance(query, long)):
		key = query
	else:
		cur.execute("SELECT `id` FROM people WHERE q=%s", (query, ))
		data = cur.fetchone()
#		print cur._last_executed
		if data:
			key = data['id']
		else:
			return False
	return key


def getinfo(query):
 	global cur
 	if isinstance(query, int):
 		cur.execute("SELECT q, name, `id`, stick, related, paths, paths_test, deadend, notes FROM people WHERE id=%s", (query, ))
 	else:
		cur.execute("SELECT q, name, `id`, stick, related, paths, paths_test, deadend, notes FROM people WHERE name=%s OR q=%s OR name_initially_searched=%s", (query, query, query, ))
 	data = cur.fetchone()
	if data:
		name = data['name'] if data['name'] else data['q']
		notes = data['notes'] if data['notes'] else ''
		type = 'error' if data['deadend'] else 'normal'
		return {'id': data['id'], 'stick': data['stick'], 'related': data['related'], 'type': type, 'rowcount': cur.rowcount, 'paths': data['paths'], 'paths_test': data['paths_test'], 'name': name, 'notes': notes}
	else:
		return False

def get_links_to(query, returnval = 'strings'):
	global cur
	links_to = []
	key = convert_query_to_key(query)
 		
	# jesus christ, I thought python was supposed to make my life easier
	key = str(key)
	
	cur.execute("SELECT * FROM `people` WHERE related REGEXP '([|[[:space:]])%s(,|])'", (int(key), ))
	if cur.rowcount:
		rows = cur.fetchall()
		for row in rows:
			if returnval == 'strings':
				links_to.append(row['q'])
			else: 
				links_to.append(row['id'])
		return links_to
	else:
		return False

def set_meta(key, val):
	cur.execute("SELECT * FROM meta WHERE meta_key=%s", (key, ))
	if cur.rowcount:
		cur.execute("UPDATE meta SET meta_val=%s WHERE meta_key=%s", (val, key, ))
	else:
		cur.execute("INSERT INTO meta (`meta_key`, `meta_val`) VALUES (%s, %s)", (key, val, ))

def get_meta(key):
	cur.execute("SELECT * FROM meta WHERE meta_key=%s", (key, ))
	data = cur.fetchone()
	if data:
		return data['meta_val']
	else:
		return False
		
def add_to_errors(q, name, errmessage = 'error - '):
	cur.execute("INSERT INTO people (`name_initially_searched`, `name`, `q`, `notes`, deadend) VALUES (%s, %s, %s, %s, 1);", (q, name, q, errmessage, ))
	id = cur.lastrowid
	return {'id': id, 'stick': None, 'type': 'error'}

def move_to_errors(q, errmessage = 'moved from people'):
	info = getinfo(q)
	if (not info) or (info['id'] < 0): return False
	
	logging.info("DEADEND: " + q + " " + str(info['id']) + " " + errmessage)
	cur.execute("UPDATE people SET deadend=1, notes=%s WHERE id=%s", (errmessage, info['id']))

def removeFromDatabase(q):
	key = convert_query_to_key(q)
	if not key:
		return
	cur.execute('DELETE FROM `people` WHERE `id`=%s', (key,))	
	links = get_links_to(q, 'keys')
	if not links:
		return
	for link in links:
		info = getinfo(link)
		print info['name']
		related = json.loads(info['related'])
		related.remove(key)
		print related
		cur.execute('UPDATE `people` SET `related`=%s WHERE `id`=%s', (json.dumps(related), info['id'], ))	

def print_paths(id):
	q = getinfo(id).get('paths')
	current_paths = json.loads(q)
	for p in current_paths:
		print "Paths to: " + getinfo(int(p)).get('name')
		for path in current_paths.get(p):
			for id in path:
				print getinfo(id).get('name')
			print

def add_paths_to(paths, source, target):
	target = str(target)
	info = getinfo(source)['paths']
	if info:
		info = json.loads(info)
	else:
		info = {}

	if target in info:
		info[target].extend(paths)
		info[target] = reduce(lambda l, x: l if x in l else l+[x], info[target], [])
	else:
		info[target] = paths
	cur.execute('UPDATE `people` SET `paths`=%s WHERE `id`=%s', (json.dumps(info), source, ))
	