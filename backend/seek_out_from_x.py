#in target mode, this seeks inward to X, finds all paths of X is on the Knowledge Graph of Y. This function is either limited by depth, whereupon we exhaust all routes in from that depth
#in source mode, we stop when we've figured out all shortest paths from source to target

import MySQLdb as mdb
import sys, json, argparse, pickle, config
from scraped_db_lib import *

try:
    con = mdb.connect(host=config.mysql['host'], user=config.mysql['user'], passwd=config.mysql['passwd'], db=config.mysql['db'], charset='utf8')
    cur = con.cursor(mdb.cursors.DictCursor)
except mdb.Error, e:
	print "Error %d: %s" % (e.args[0],e.args[1])
	sys.exit(1)

def seeker(queue):
	if not queue:
		print "false"
		return False
	if not len(queue):
		print "***********done"
		return False
	
	path_to_check = queue.pop(0)
# do stuff that happens when we've exhausted a depth here
#	if current_depth not != len(path_to_check):
	current_depth = len(path_to_check)

	if current_depth > args.depth:
		print "*********** done >" + str(args.depth)
		sys.exit()
	key_to_check = path_to_check[0]
	key_to_check = convert_query_to_key(key_to_check)

	info = getinfo(key_to_check)
	if not info:
		print "***********NO INFO"
		return queue

	if (key_to_check in seeker.solutions[seeker.target_key]):
		#we've solved this already
		# remove all longer matched paths from the queue
		to_remove = [path for path in queue if path[0] == key_to_check and len(path) == current_depth]
		for re in to_remove:
			queue.remove(re)
		print "skipping " + str(key_to_check)
		return queue
		
	logme('')
	logme("***** " + info['name'] + " key:" + str(key_to_check) + " depth:" + str(current_depth) + " remaining:" + str(len(queue)))
	logme("checking " + str(path_to_check))
	
	if info['paths']:
		current_paths = json.loads(info['paths'])
		#json disallows numeric keys, so we convert them back
		current_paths = {int(k):v for k,v in current_paths.items()}
		
		if seeker.target_key in current_paths:
			shortest_path_length = len(current_paths[seeker.target_key][0])

			#this is used to see if we've exhausted multiple solutions (for depth > 2)
			if current_depth > shortest_path_length:
				#we've solved this already, so we'll make note of it and not check it again 
				seeker.solutions[seeker.target_key].add(key_to_check)

				logme("++solved for " + info['name'])
				return queue
			elif path_to_check not in current_paths[seeker.target_key]:
				if key_to_check not in seeker.solutions[seeker.target_key]:
					if len(path_to_check) <= args.depth:
						current_paths[seeker.target_key].append(path_to_check)
			else:
			#what does this do?
				pass
#				print current_paths
				#commenting out this next line allows continuing on if we're running this again to a deeper depth
#				return queue
		else:
			#init this key in current_paths
			current_paths[seeker.target_key] = [path_to_check]
	else:
		#init current_paths, init this key in current_paths
		current_paths = {}
		current_paths[seeker.target_key] = [path_to_check]	

	add_paths_to(current_paths[seeker.target_key], key_to_check, seeker.target_key)
#	cur.execute("UPDATE people SET paths=%s WHERE id=%s", (json.dumps(current_paths), key_to_check, ))
#	logme(cur._last_executed)

	links = get_links_to(key_to_check, 'int')
	if not links:
		logme("***********NO LINKS TO " + str(key_to_check))
		seeker.solutions[seeker.target_key].add(key_to_check)
		return queue
		
	if current_depth < args.depth:
		for link in links:
			if link not in seeker.solutions[seeker.target_key]:
				newpath = path_to_check[:]
				newpath.insert(0, link)
				queue.append(newpath)
				logme("Will test " + str(link))

#Hmm, this might conflict with the logic directly above	
	if current_depth < 3:
		seeker.solutions[seeker.target_key].add(key_to_check)

	return queue
	
def init_seek(target):
	counter = 0
	queries = [[seeker.target_key]]
	if args.reset_hard:
		cur.execute("DELETE FROM meta WHERE meta_key='pathfinder_queries'")
		cur.execute("DELETE FROM meta WHERE meta_key='seeker_solutions'")
		cur.execute("DELETE FROM meta WHERE meta_key='narrow_visited'")
		#delete all paths
		cur.execute("UPDATE people SET paths=NULL")
	elif args.reset:
		cur.execute("DELETE FROM meta WHERE meta_key='pathfinder_queries'")
		cur.execute("DELETE FROM meta WHERE meta_key='seeker_solutions'")
		cur.execute("DELETE FROM meta WHERE meta_key='narrow_visited'")

		#delete paths to target
 		cur.execute("SELECT `id`, paths FROM people WHERE paths LIKE '%\"" + str(seeker.target_key) + "\":%'")
 		rows = cur.fetchall()
		for row in rows:
			paths = json.loads(row['paths'])
			del paths[str(seeker.target_key)]
			if len(paths) == 0:
				cur.execute("UPDATE people SET paths=NULL WHERE id=%s", (row['id'], ))
			else:
				cur.execute("UPDATE people SET paths=%s WHERE id=%s", (json.dumps(paths), row['id'], ))
	else:
		queries = get_meta('pathfinder_queries')
		if (queries) and (len(json.loads(queries))):
			queries = json.loads(queries)

	seeker.solutions = get_meta('seeker_solutions')
	if (seeker.solutions) and (len(pickle.loads(seeker.solutions))):
		seeker.solutions = pickle.loads(seeker.solutions)
	else:
		seeker.solutions = {}

	if not seeker.target_key in seeker.solutions:
		seeker.solutions[seeker.target_key] = set()
		
	if args.source:
		source_info = getinfo(args.source)
		if source_info == False:
			print "invalid source"
			sys.exit()
		else:
			seeker.source_id = source_info.get('id')
			seeker.target_key = convert_query_to_key(args.target)
			narrow_domain.count = 0
			visited = get_meta('narrow_visited')
			if (visited):
				visited = pickle.loads(visited)
				narrow_domain(visited)
			else:
				narrow_domain()
	else:
		while queries:
			queries = seeker(queries)
			counter += 1
			if counter % 100 == 0:
				logme("counter:" + str(counter))
				set_meta('pathfinder_queries', json.dumps(queries))
				set_meta('seeker_solutions', pickle.dumps(seeker.solutions))
				logme("storing meta")
	sys.exit()

def logme(msg):
	if args.verbose:
		print msg

#trying a new approach. first seek out from target and get sets of steps. Once source is found, work back from there...	
#Alex Jones is 18467
#
def narrow_domain(visited = False):
	related = set()
	links_to_source = []
	if not visited:
		visited = []
		links = set()
		links.add(seeker.target_key)
		visited.append(links)
	for id in visited[0]:
		links = get_links_to(id, 'int')
		if not links:
			continue
		links = set(links)
		related |= links
		if seeker.source_id in links:
			print "FOUND " + str(id)
			print getinfo(id)
			print seeker.source_id
			links_to_source.append(id)
	#remove previous hops from related set
	for hop in visited:
		related = related - hop
	visited.insert(0, related)
	logme(narrow_domain.count)
	logme(visited)
	logme('')
	if seeker.source_id not in related:
		narrow_domain.count += 1
		set_meta('narrow_visited', pickle.dumps(visited))
		return narrow_domain(visited)
	print links_to_source
	
	def work_back_from_source(paths):
		last_hop = visited.pop(0)
		newpaths = []
		for path in paths:
			rel = set(json.loads(getinfo(path[-1])['related']))
			#find intersection of related paths and paths in current hop
			potential_paths = last_hop & rel
			for potential_path in potential_paths:
				newpath = path[:]
				newpath.append(potential_path)
				if potential_path == seeker.target_key:
					work_back_from_source.solutions.append(newpath)
				newpaths.append(newpath)
		if len(work_back_from_source.solutions):
			add_paths_to(work_back_from_source.solutions, seeker.source_id, seeker.target_key)
			print_paths(seeker.source_id)
			sys.exit()
		else:
			work_back_from_source(newpaths)

	#convert paths into list of lists
	paths = [[p] for p in links_to_source]
	#make all paths begin with source_id
	[[path.insert(0,seeker.source_id)] for path in paths]
	work_back_from_source.solutions = []
	work_back_from_source(paths)


parser = argparse.ArgumentParser(description='Seek paths in Knowledge Graph.')
parser.add_argument('target', default='duchamp artist', help='seek links to target limited by depth')
parser.add_argument('--depth', default=5, type=int, help='depth to seek')
parser.add_argument('--source', help='seek links to target from this person')
parser.add_argument('--reset', action='store_true', help='clear all paths to target from db')
parser.add_argument('--reset_hard', action='store_true', help='clear all paths from db')
parser.add_argument('--verbose', action='store_true', help='more log info')
args = parser.parse_args()

seeker.target_key = convert_query_to_key(args.target)

init_seek(args.target)

sys.exit()
