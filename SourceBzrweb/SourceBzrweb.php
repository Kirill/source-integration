<?php
# Copyright (C) 2009 Kirill Krasnov, www.kraeg.ru
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourcePlugin.class.php' ) ) {
	return;
}

require_once( config_get( 'core_path' ) . 'url_api.php' );

class SourceBzrwebPlugin extends MantisSourcePlugin {
	public function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = '0.0.1';
		$this->requires = array(
			'MantisCore' => '1.2.0',
			'Source' => '0.16',
			'Meta' => '0.1',
		);

		$this->author = 'Kirill Krasnov';
		$this->contact = 'krasnovforum@gmail.com';
		$this->url = 'http://www.kraeg.ru';
	}

	public $type = 'bzrweb';

	function get_types( $p_event ) {
		return array( 'bzrweb' => plugin_lang_get( 'bzrweb' ) );
	}

	function show_type( $p_event, $p_type ) {
		if ( 'bzrweb' == $p_type ) {
			return plugin_lang_get( 'bzrweb' );
		}
	}

	function show_changeset( $p_event, $p_repo, $p_changeset ) {
		if ( 'bzrweb' != $p_repo->type ) {
			return;
		}

		$t_ref = substr( $p_changeset->revision, 0, 8 );
		$t_branch = $p_changeset->branch;

		return "$t_branch $t_ref";
	}

	function show_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'bzrweb' != $p_repo->type ) {
			return;
		}

		return  "$p_file->action - $p_file->filename";
	}

	function uri_base( $p_repo ) {
		$t_uri_base = $p_repo->info['bzrweb_root'] . '?p=' . $p_repo->info['bzrweb_project'] . ';';

		return $t_uri_base;
	}

	function url_repo( $p_event, $p_repo, $t_changeset=null ) {
		if ( 'bzrweb' != $p_repo->type ) {
			return;
		}

		return $this->uri_base( $p_repo ) . ( $t_changeset ? 'h=' . $t_changeset->revision : '' );
	}

	function url_changeset( $p_event, $p_repo, $p_changeset ) {
		if ( 'bzrweb' != $p_repo->type ) {
			return;
		}

		return $this->uri_base( $p_repo ) . 'a=commitdiff;h=' . $p_changeset->revision;
	}

	function url_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'bzrweb' != $p_repo->type ) {
			return;
		}

		return $this->uri_base( $p_repo ) . 'a=blob;f=' . $p_file->filename .
			';h=' . $p_file->revision . ';hb=' . $p_changeset->revision;
	}

	function url_diff( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'bzrweb' != $p_repo->type ) {
			return;
		}

		return $this->uri_base( $p_repo ) . 'a=blobdiff;f=' . $p_file->filename .
			';h=' . $p_file->revision . ';hb=' . $p_changeset->revision . ';hpb=' . $p_changeset->parent;
	}

	function update_repo_form( $p_event, $p_repo ) {
		if ( 'bzrweb' != $p_repo->type ) {
			return;
		}

		$t_bzrweb_root = null;
		$t_bzrweb_project = null;

		if ( isset( $p_repo->info['bzrweb_root'] ) ) {
			$t_bzrweb_root = $p_repo->info['bzrweb_root'];
		}

		if ( isset( $p_repo->info['bzrweb_project'] ) ) {
			$t_bzrweb_project = $p_repo->info['bzrweb_project'];
		}

		if ( isset( $p_repo->info['master_branch'] ) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = 'master';
		}
?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'bzrweb_root' ) ?></td>
<td><input name="bzrweb_root" maxlength="250" size="40" value="<?php echo string_attribute( $t_bzrweb_root ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'bzrweb_project' ) ?></td>
<td><input name="bzrweb_project" maxlength="250" size="40" value="<?php echo string_attribute( $t_bzrweb_project ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'master_branch' ) ?></td>
<td><input name="master_branch" maxlength="250" size="40" value="<?php echo string_attribute( $t_master_branch ) ?>"/></td>
</tr>
<?php
	}

	function update_repo( $p_event, $p_repo ) {
		if ( 'bzrweb' != $p_repo->type ) {
			return;
		}

		$f_bzrweb_root = gpc_get_string( 'bzrweb_root' );
		$f_bzrweb_project = gpc_get_string( 'bzrweb_project' );
		$f_master_branch = gpc_get_string( 'master_branch' );

		$p_repo->info['bzrweb_root'] = $f_bzrweb_root;
		$p_repo->info['bzrweb_project'] = $f_bzrweb_project;
		$p_repo->info['master_branch'] = $f_master_branch;

		return $p_repo;
	}

	function precommit( $p_event ) {
		# TODO: Implement real commit sequence.
		return;
	}

	function commit( $p_event, $p_repo, $p_data ) {
		if ( 'bzrweb' != $p_repo->type ) {
			return;
		}

                # The -d option from curl requires you to encode your own data.
                # Once it reaches here it is decoded. Hence we split by a space
                # were as the curl command uses a '+' character instead.
                # i.e. DATA=`echo $INPUT | sed -e 's/ /+/g'`
                list ( , $t_commit_id, $t_branch) = split(' ', $p_data);
                list ( , , $t_branch) = split('/', $t_branch);
                if ($t_branch != $p_repo->info['master_branch'])
                {
                        return;
                }

                return $this->import_commits($p_repo, null, $t_commit_id, $t_branch);
	}

	function import_full( $p_event, $p_repo ) {
		if ( 'bzrweb' != $p_repo->type ) {
			return;
		}
		echo '<pre>';

		$t_branch = $p_repo->info['master_branch'];
		if ( is_blank( $t_branch ) ) {
			$t_branch = 'master';
		}

		$t_branches = map( 'trim', explode( ',', $t_branch ) );
		$t_changesets = array();

		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		foreach( $t_branches as $t_branch ) {
			$t_query = "SELECT parent FROM $t_changeset_table
				WHERE repo_id=" . db_param() . ' AND branch=' . db_param() .
				'ORDER BY timestamp ASC';
			$t_result = db_query_bound( $t_query, array( $p_repo->id, $t_branch ), 1 );

			$t_commits = array( $t_branch );

			if ( db_num_rows( $t_result ) > 0 ) {
				$t_parent = db_result( $t_result );
				echo "Oldest '$t_branch' branch parent: '$t_parent'\n";

				if ( !empty( $t_parent ) ) {
					$t_commits[] = $t_parent;
				}
			}

			$t_changesets = array_merge( $t_changesets, $this->import_commits( $p_repo, $this->uri_base( $p_repo ), $t_commits, $t_branch  ) );
		}

		echo '</pre>';

		return $t_changesets;
	}

	function import_latest( $p_event, $p_repo ) {
		return $this->import_full( $p_event, $p_repo );
	}

	function import_commits( $p_repo, $p_uri_base, $p_commit_ids, $p_branch='' ) {
		static $s_parents = array();
		static $s_counter = 0;

		if ( is_array( $p_commit_ids ) ) {
			$s_parents = array_merge( $s_parents, $p_commit_ids );
		} else {
			$s_parents[] = $p_commit_ids;
		}

		$t_changesets = array();

		while( count( $s_parents ) > 0 && $s_counter < 200 ) {
			$t_commit_id = array_shift( $s_parents );

			echo "Retrieving $t_commit_id ... ";

			$t_commit_url = $this->uri_base( $p_repo ) . 'a=commit;h=' . $t_commit_id;
			$t_input = url_get( $t_commit_url );

			if ( false === $t_input ) {
				echo "failed.\n";
				continue;
			}

			list( $t_changeset, $t_commit_parents ) = $this->commit_changeset( $p_repo, $t_input, $p_branch );
			if ( !is_null( $t_changeset ) ) {
				$t_changesets[] = $t_changeset;
			}

			$s_parents = array_merge( $s_parents, $t_commit_parents );
		}

		$s_counter = 0;
		return $t_changesets;
	}

	function commit_changeset( $p_repo, $p_input, $p_branch='' ) {

		$t_input = str_replace( array(PHP_EOL, '&lt;', '&gt;', '&nbsp;'), array('', '<', '>', ' '), $p_input );

		# Exract sections of commit data and changed files
		$t_input_p1 = strpos( $t_input, '<div class="title_text">' );
		$t_input_p2 = strpos( $t_input, '<div class="list_head">' );
		if ( false === $t_input_p1 || false === $t_input_p2 ) {
			echo 'commit data failure.';
			var_dump( strlen( $t_input ), $t_input_p1, $t_input_p2 );
			die();
		}
		$t_bzrweb_data = substr( $t_input, $t_input_p1, $t_input_p2 - $t_input_p1 );

		$t_input_p1 = strpos( $t_input, '<table class="diff_tree">' );

		if ( false === $t_input_p1) {
			$t_input_p1 = strpos( $t_input, '<table class="combined diff_tree">' );
		}

		$t_input_p2 = strpos( $t_input, '<div class="page_footer">' );
		if ( false === $t_input_p1 || false === $t_input_p2 ) {
			echo 'file data failure.';
			var_dump( strlen( $t_input ), $t_input_p1, $t_input_p2 );
			die();
		}
		$t_bzrweb_files = substr( $t_input, $t_input_p1, $t_input_p2 - $t_input_p1 );

		# Get commit revsion and make sure it's not a dupe
		preg_match( '#<tr><td>commit</td><td class="sha1">([a-f0-9]*)</td></tr>#', $t_bzrweb_data, $t_matches );
		$t_commit['revision'] = $t_matches[1];

		echo "processing $t_commit[revision] ... ";
		if ( !SourceChangeset::exists( $p_repo->id, $t_commit['revision'] ) ) {

			# Parse for commit data
			preg_match( '#<tr><td>author</td><td>([^<>]*) <([^<>]*)></td></tr>'.
				'<tr><td></td><td> \w*, (\d* \w* \d* \d*:\d*:\d*)#', $t_bzrweb_data, $t_matches );
			$t_commit['author'] = $t_matches[1];
			$t_commit['author_email'] = $t_matches[2];
			$t_commit['date'] = date( 'Y-m-d H:i:s', strtotime( $t_matches[3] ) );

			if( preg_match( '#<tr><td>committer</td><td>([^<>]*) <([^<>]*)></td></tr>#', $t_bzrweb_data, $t_matches ) ) {
				$t_commit['committer'] = $t_matches[1];
				$t_commit['committer_email'] = $t_matches[2];
			}

			$t_parents = array();
			if( preg_match_all( '#<tr><td>parent</td><td class="sha1"><a [^<>]*>([a-f0-9]*)</a></td>#', $t_bzrweb_data, $t_matches ) ) {
				foreach( $t_matches[1] as $t_match ) {
					$t_parents[] = $t_commit['parent'] = $t_match;
				}
			}

			preg_match( '#<div class="page_body">(.*)</div>#', $t_bzrweb_data, $t_matches );
			$t_commit['message'] = trim( str_replace( '<br/>', PHP_EOL, $t_matches[1] ) );

			# Strip ref links and signoff spans from commit message
			$t_commit['message'] = preg_replace( array(
					'@<a[^>]*>([^<]*)<\/a>@',
					'@<span[^>]*>([^<]*<[^>]*>[^<]*)<\/span>@', #finds <span..>signed-off by <email></span>
				), '$1', $t_commit['message'] );

			# Parse for changed file data
			$t_commit['files'] = array();

			preg_match_all( '#<tr class="(?:light|dark)"><td><a class="list" href="[^"]*;h=(\w+);[^"]*">([^<>]+)</a></td>'.
				'<td>(?:<span class="file_status (\w+)">[^<>]*</span>)?</td>#',
				$t_bzrweb_files, $t_matches, PREG_SET_ORDER );

			foreach( $t_matches as $t_file_matches ) {
				$t_file = array();
				$t_file['filename'] = $t_file_matches[2];
				$t_file['revision'] = $t_file_matches[1];

				if ( isset( $t_file_matches[3] ) ) {
					if ( 'new' == $t_file_matches[3] ) {
						$t_file['action'] = 'add';
					} else if ( 'deleted' == $t_file_matches[3] ) {
						$t_file['action'] = 'rm';
					}
				} else {
					$t_file['action'] = 'mod';
				}

				$t_commit['files'][] = $t_file;
			}

			$t_changeset = new SourceChangeset( $p_repo->id, $t_commit['revision'], $p_branch,
				$t_commit['date'], $t_commit['author'], $t_commit['message'], 0,
				( isset( $t_commit['parent'] ) ? $t_commit['parent'] : '' ) );

			$t_changeset->author_email = $t_commit['author'];
			$t_changeset->committer = $t_commit['committer'];
			$t_changeset->committer_email = $t_commit['committer_email'];

			foreach( $t_commit['files'] as $t_file ) {
				$t_changeset->files[] = new SourceFile( 0, $t_file['revision'], $t_file['filename'], $t_file['action'] );
			}

			$t_changeset->save();

			echo "saved.\n";
			return array( $t_changeset, $t_parents );
		} else {
			echo "already exists.\n";
			return array( null, array() );
		}
	}
}
