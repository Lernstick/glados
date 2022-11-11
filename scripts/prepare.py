#!/usr/bin/env python3
#
# This is the main entry point for the script that sets up the client system.
# All this is executed on the client system with root permissions. All
# functionality must be present in this file or in the directory
#
#   /var/lib/lernstick-exam-client/persistent/var/lib/lernstick-exam-client
#
# on the client system, since this script is executed as follows:
#
#   cat prepare.py | ssh <flags> python3 - <flags>
#

import argparse
import sys
import os
import json
import logging
import shutil # shell commands like cp, ...
import requests # requests.get()
import fileinput # edit files inplace
import fnmatch # fnmatch.fnmatch()
import tempfile # tempfile.NamedTemporaryFile()
import re
import pathlib
import textwrap # dedent()
from lxml import etree # for support of nsmap

# append to the interpreter’s search path for modules
directory = "/var/lib/lernstick-exam-client/persistent/var/lib/lernstick-exam-client"
sys.path.append(directory)
import functions as helpers # 

#constants
logFile = "/var/log/searchExamServer.log" # same as searchExamServer.py on the client
INITRD = "/run/initramfs"
exam_user = "user"
exam_uid = 1000
exam_gid = 1000
root_uid = 0
root_gid = 0
infoFile = f"{INITRD}/info"
configFile = f"{INITRD}/config.json"
mountFile = f"{INITRD}/mount"
mnConnectionsPath = "/etc/NetworkManager/system-connections"

# @see https://docs.python.org/3/library/audit_events.html
audit_events = [
    'os.chmod',
    'os.chown',
    'os.mkdir',
    'os.remove',
    'os.rename',
    'os.rmdir',
    'os.symlink'
    'shutil.*',
]

# Creates a new function decorator @arg_logger which logs the function name,
# its arguments and return values if args.debug is True.
from functools import wraps
def arg_logger(func):
    @wraps(func)
    def function_logger(*args, **kwargs):
        logger.debug(f"calling {func.__name__}() with {args=} and {kwargs=}")
        r = func(*args, **kwargs)
        logger.debug(f"call of {func.__name__}() returned {r}")
        return r
    return function_logger

# Handles any cleanup on exit
def clean_exit(reason = None, exit_code = 0):
    if exit_code == 0:
        logger.info(f'Exiting gracefully (reason: {reason})')
    else:
        logger.error(f'Exiting gracefully (reason: {reason})')

    # unmount the filesystem(s)
    helpers.run(f'umount -v {INITRD}/newroot || true')
    helpers.run(f'umount -v -l {INITRD}/{{base,exam,tmpfs}} || true')

    logger.info('prepare script stopped')
    logger.info('-'*20 + '[prepare]' + '-'*20)

    exit(exit_code)

# exits with the message mesg, kwargs are for the zenity window
def exit_with_error_message(mesg, **kwargs):
    logger.error(mesg)
    default_args = {
        'title': 'Error',
        'error': True,
        'width': 300,
        'text': mesg,
        'no-markup': True
    }
    kwargs = {**default_args, **kwargs} # merge the args, where the second has priority

    helpers.run(helpers.zenity(**kwargs), env = env)
    clean_exit(mesg, exit_code = 1)

# obtain the url, kwargs is for http.get()
def http_get(url, **kwargs):
    logger.debug(f"getting URL: {url}")
    try:
        r = requests.get(url, **kwargs)
    except requests.exceptions.RequestException as e:  # This is the correct syntax
        logger.error(repr(e))
        r = requests.models.Response()
        r.status_code = -1
        r.code = str(e)
        r.error_type = repr(e)
    return r

# retrieves the configuration json and stores it to configFile
def get_config_from_server(url):
    r = http_get(url)
    if r.status_code != 200:
        exit_with_error_message(f"Failed while fetching the system config (status code: {r.status_code}).")

    helpers.file_put_contents(configFile, json.dumps(r.json()))
    return r.json()

"""
Copies a file, preserving owner and permissions

@param string src: source file
@param string dst: destination file
@param bool fail_ok: raise if copying fails
@param bool dry_run: only log (debug) what would be copied, without actually copying
@param *args, **kwargs: remaining arguments for the shutil.copy2 function
@return bool: fail or success
"""
def copy(src, dst, fail_ok = False, dry_run = False, *args, **kwargs):
    # copy content, stat-info (mode too), timestamps...
    if not dry_run:
        try:
            shutil.copy2(src, dst, *args, **kwargs)
        except shutil.SameFileError as e:
            logger.warning(f'{src} and {dst} are the same file')
            pass
        except Exception as e:
            if fail_ok:
                logger.debug(f'failed to copy {src} -> {dst}')
            else:
                logger.error(f'failed to copy {src} -> {dst}')
                raise
            return False

    # copy owner and group
    st = os.stat(src)
    if not dry_run: os.chown(dst, st.st_uid, st.st_gid)
    logger.debug(f'copied {src} -> {dst}')
    return True

# copies a file tree, preserving owner and permissions
def copytree(src, dest, copy_function = copy, *args, **kwargs):
    ret = True
    if os.path.isdir(src):
        if not os.path.isdir(dest):
            os.makedirs(dest, exist_ok = True)
        for f in os.listdir(src):
            ret = ret and copytree(os.path.join(src, f), os.path.join(dest, f), copy_function, *args, **kwargs)
        return ret
    else:
        return copy_function(src, dest, *args, **kwargs)

# removes a file
def remove(file, fail_ok = False):
    try:
        os.remove(file)
        return True
    except Exception as e:
        if fail_ok:
            logger.debug(f'failed to remove {file}')
        else:
            logger.error(f'failed to remove {file}')
            raise
        return False

"""
Searches for a regex in a file line by line and replaces it.

@param string find: regex to search
@param string replace: string to replace, line is deleted if replace is None
@param string in_file: path to the file
@param string out_file: path to the file to store the changes (if not given it's the [[in_file]])
"""
@arg_logger
def file_regex(find, replace, in_file, out_file = None):
    replace = '' if replace is None else replace
    in_context = fileinput.FileInput(in_file, backup = '.bak', inplace = out_file is None)
    out_context = open('/dev/null' if out_file is None else out_file, 'w')
    old = sys.stdout
    with in_context as in_fh, out_context as out_fh:
        sys.stdout = sys.stdout if out_file is None else out_fh
        for line in in_fh:
            print(re.sub(find, replace, line), end = '')
    sys.stdout = old

"""
Extracts fields from files by pattern matching

@param int field: the field number separated by [[separator]] to extract. if 0
 the whole line will be returned, if -1 all fields are returned in a list
@param int mfield: the field number separated by [[separator]] to match [
 [pattern]] against. if 0 the whole line is matched against [[pattern]] be
 returned
@param string pattern: the pattern to match against (a regex)
@param string separator: separator as a regex with which to split the fields
@return None|list|string
"""
def extract(field, mfield, pattern, file, separator = r'\s'):
    with open(file, 'r') as f:
        for line in f:
            #lines = line.split(separator)
            lines = re.split(separator, line)
            try:
                haystack = line if mfield == 0 else lines[mfield-1]
                #if pattern in haystack:
                if re.match(pattern, haystack):
                    if field == 0:
                        r = line
                    elif field == -1:
                        r = lines
                    else:
                        r = lines[field-1]
                    logger.debug(f"extracted {r} from {file}")
                    return r
            except IndexError:
                pass
    logger.error(f"pattern {pattern} not found in field {mfield} in {file} ({separator = })")
    return None

@arg_logger
def mount(args, src, dst = None, only_return_command = False):
    logger.debug(f"Mounting {src} -> {dst} using args {args}")
    cmd = f'mount {args} "{src}"' if dst is None else f'mount {args} "{src}" "{dst}"'
    if only_return_command:
        return cmd
    else:
        ret, out = helpers.run(cmd)
        return ret

@arg_logger
def chown(path, uid, gid, recursive = False):
    logger.debug(f"changing owner of {path} to {uid}:{gid}, {recursive = }")
    if not recursive:
        os.chown(path, uid, gid)
    else:
        for root, dirs, files in os.walk(path):
            for obj in dirs+files:
                os.chown(os.path.join(root, obj), uid, gid)

@arg_logger
def mount_rootfs(newroot, home):
    logger.info(f"mounting root filesystem to {newroot}")

    exam_squashfs = f"{INITRD}/squashfs/exam.squashfs"
    exam_zip = f"{INITRD}/squashfs/exam.zip"

    # determine where filesystem.squashfs is
    filesystem_squashfs = extract(2, 2, r'.*filesystem.squashfs$', '/proc/mounts')

    mount('-v --bind' if os.path.isdir(filesystem_squashfs) else '-v', filesystem_squashfs, f"{INITRD}/base")
    
    if os.path.isfile(exam_squashfs) and os.access(exam_squashfs, os.R_OK):
        mount('-v', exam_squashfs, f"{INITRD}/exam")

        # find out whether the squashfs is an overlayfs
        _, fstype = helpers.run(f'unsquashfs -ll {INITRD}/squashfs/exam.squashfs | awk \'$1~/^c/&&$3=="0,"&&$4=="0"{{print "overlay"; exit}}\'')
        if fstype != 'overlay':
            # find out whether the squashfs is an aufs
            _, fstype = helpers.run(f'unsquashfs -ll {INITRD}/squashfs/exam.squashfs | awk \'$0~/\/\.wh\./{{print "aufs"; exit}}\'')

    if os.path.isfile(exam_zip) and os.access(exam_zip, os.R_OK):
        mount('-v -t tmpfs', 'tmpfs', f"{INITRD}/tmpfs")
        helpers.run(f"unzip -o {INITRD}/squashfs/exam.zip -d {INITRD}/tmpfs")
        fstype = "zip"

        chown(f"{INITRD}/tmpfs/{home}", 1000, 1000, recursive = True)
        chown(f"{INITRD}/tmpfs/{home}/Screenshots", 0, 0, recursive = True)
        chown(f"{INITRD}/tmpfs/{home}/.Screenshots", 0, 0, recursive = True)


    # mount the whole filesystem, the result filesystem looks like this
    # +---------------+
    # | tmpfs (rw)    |
    # +---------------+
    # | zip (ro)      |
    # +---------------+
    # | squashfs (ro) |
    # +---------------+
    # | base (ro)     |
    # +---------------+
    os.makedirs(f"{INITRD}/work", exist_ok = True)
    if fstype == 'aufs':
        # in there are whiteouts for aufs (\.wh\.*) and no whiteouts for overlayfs (character devices with 0/0) it is an aufs filesystem
        mount(f'-v -t aufs -o br={INITRD}/backup=rw:{INITRD}/tmpfs=ro:{INITRD}/exam=ro:{INITRD}/base=ro', 'none', f'{INITRD}/{newroot}')
        mount_cmd = mount('-v -t aufs -o br=/backup=rw:/tmpfs=ro:/exam=ro:/base=ro', 'none', f'/{newroot}', only_return_command = True)
    else:
        # in all other cases the filesystem in treated as overlay
        mount(f'-v -t overlay overlay -o lowerdir={INITRD}/tmpfs:{INITRD}/exam:{INITRD}/base,upperdir={INITRD}/backup,workdir={INITRD}/work', f'{INITRD}/{newroot}', None)
        mount_cmd = mount('-v -t overlay overlay -o lowerdir=/tmpfs:/exam:/base,upperdir=/backup,workdir=/work', f'/{newroot}', only_return_command = True)

        # remount /run with bigger size (50% of physical RAM)
        mount('-v -n -o remount,size=50%', '/run', None)
    
    helpers.file_put_contents(mountFile, f"{mount_cmd}\n")

"""
Update Gnome3 dash by adding an entry to it.

The entry is added to the system database as well as to the user database.

@param string entry: the entry to add as a .desktop file
"""
@arg_logger
def add_dash_entry(entry, env = {}):
    logger.debug(f"adding dash entry for {entry}")

    local_user_db = '/home/user/.config/dconf/user'
    system_db = f'{INITRD}/newroot/etc/dconf/db/local.d/01-gnome-favorite-apps'
    user_db = f'{INITRD}/newroot/home/user/.config/dconf/user'

    # place entry in "favorite apps" of Gnome3's dash in the system-db
    apps = get_dash_entries("system", system_db, env=env)
    apps.append(f"'{entry}'") # add the entry to the list
    set_dash_entries("system", apps, system_db, env=env)

    # place entry in "favorite apps" of Gnome3's dash in the user-db
    apps = get_dash_entries("user", user_db, env=env)
    apps.append(f"'{entry}'")
    set_dash_entries("user", apps, user_db, env=env)

    return (f"'{entry}'" in get_dash_entries("user", user_db, env=env) and
            f"'{entry}'" in get_dash_entries("system", system_db, env=env))


"""
Set all the dash entries

@param string type: query one of the databases (system or user)
@param list entries: entries to set
@param string db: the database filepath
@param dict env: environment variables, such as DISPLAY, XAUTHORITY, ...
@return bool: success/fail
"""
@arg_logger
def set_dash_entries(type, entries, db, env={}):
    local_user_db = '/home/user/.config/dconf/user'
    val = ", ".join(list(set(entries)))
    os.makedirs(os.path.dirname(db), mode = 0o755, exist_ok = True)

    if type == "system":
        helpers.file_put_contents(db, f'[org/gnome/shell]\nfavorite-apps=[{val}]\n')

        # this touch is needed, because dconf update is not rebuilding the database if the
        # directory containing the rules has the same mtime as before
        pathlib.Path(os.path.dirname(db)).touch()
        helpers.run(f"chroot {INITRD}/newroot dconf update")
        helpers.run('sync')

    elif type == "user":
        copy(local_user_db, f'{local_user_db}.bak')
        if os.path.exists(db):
            copy(db, local_user_db)

        helpers.run(f'sudo -u user dconf write "/org/gnome/shell/favorite-apps" "[{val}]"; sync', env=env)
        copy(local_user_db, db)
        os.rename(f'{local_user_db}.bak', local_user_db)


"""
Obtain all the dash entries

@param string type: query one of the databases (system or user)
@param string db: the database filepath
@param dict env: environment variables, such as DISPLAY, XAUTHORITY, ...
@return list: the entries
"""
@arg_logger
def get_dash_entries(type, db, env={}):
    entries = []

    if type == "system":
        if os.path.isfile(db) and os.access(db, os.R_OK):
            entries = extract(-1, 0, r'favorite-apps\=', db, separator = r'\,|\[|\]|\s')[1:]

    elif type == "user":
        if not os.path.exists(db):
            return get_default_dash_entries(env=env)

        local_user_db = '/home/user/.config/dconf/user'
        temp = tempfile.NamedTemporaryFile()
        copy(local_user_db, temp.name)
        copy(db, local_user_db)
        _, old_value = helpers.run('sudo -u user dconf read /org/gnome/shell/favorite-apps', env=env)
        copy(temp.name, local_user_db)
        if old_value != '':
            entries = re.split(r'\,|\[|\]|\s', old_value)

    return list(filter(None, entries)) # remove all empty ('') elements from list

"""
Obtain default dash entries

@param dict env: environment variables, such as DISPLAY, XAUTHORITY, ...
@return list: the entries
"""
@arg_logger
def get_default_dash_entries(env={}):
    tmp_dir = tempfile.TemporaryDirectory()
    tmp_user = 'testuser'
    tmp_db = f"{tmp_dir.name}/.config/dconf/user"
    helpers.run(f"useradd {tmp_user} -d {tmp_dir.name}")
    _, old_value = helpers.run(f'sudo -u {tmp_user} dconf read /org/gnome/shell/favorite-apps', env=env)
    entries = re.split(r'\,|\[|\]|\s', old_value) if old_value != '' else []
    helpers.run(f"userdel {tmp_user}")
    return list(filter(None, entries)) # remove all empty ('') elements from list

@arg_logger
def systemctl(service, state):
    r, _ = helpers.run(f'chroot {INITRD}/newroot systemctl {state} {service}')
    if r:
        logger.debug(f"Service {service} successfully set to {state}")
    else:
        logger.error(f"Setting {service} to {state} failed")
    return r

# config->grp_netdev
@arg_logger
def allow_network_access(allowed):
    if allowed:
        # add the user explicitely to the netdev group
        r, _ = helpers.run(f'chroot {INITRD}/newroot gpasswd -a user netdev')
    else:
        # remove user from the netdev group to prevent him from changing network connections
        r, _ = helpers.run(f'chroot {INITRD}/newroot gpasswd -d user netdev')
        file_regex('netdev', '', f'{INITRD}/newroot/etc/live/config.conf.d/user-setup.conf')
    return r

# config->allow_sudo
@arg_logger
def allow_sudo(allowed):
    sudo_file = f'{INITRD}/newroot/etc/sudoers.d/01-lernstick-exam'
    helpers.file_put_contents(sudo_file, 'user ALL=(ALL) NOPASSWD: ALL' if allowed else '')

@arg_logger
def allow_mount_external(allowed):
    logger.debug(f"allow mounting external media -> {allowed}")
    polkit_file = f"{INITRD}/newroot/etc/polkit-1/localauthority/90-mandatory.d/10-udisks2-mount.pkla"
    return add_polkit_entry(
        desc="allow user mounting and unmounting of non-system devices with self authentication",
        identity="unix-user:user",
        action="org.freedesktop.udisks2.filesystem-mount",
        res_any='yes' if allowed else 'auth_admin',
        res_inactive='yes' if allowed else 'auth_admin',
        res_active='yes' if allowed else 'auth_admin',
        file=polkit_file
    )

@arg_logger
def allow_mount_system(allowed):
    logger.debug(f"allow mounting internal media -> {allowed}")
    polkit_file = f"{INITRD}/newroot/etc/polkit-1/localauthority/90-mandatory.d/10-udisks2-mount-system.pkla"
    return add_polkit_entry(
        desc="allow user mounting and unmounting of system devices with self authentication",
        identity="unix-user:user",
        action="org.freedesktop.udisks2.filesystem-mount-system",
        res_any='yes' if allowed else 'auth_admin',
        res_inactive='yes' if allowed else 'auth_admin',
        res_active='yes' if allowed else 'auth_admin',
        file=polkit_file
    )

# enable or disable the firewall
@arg_logger
def firewall_off(off):
    logger.debug(f"firewall set to {'off' if off else 'on'}")
    if off:
        helpers.run(f'chroot {INITRD}/newroot /lib/systemd/lernstick-firewall stop')
        return systemctl('lernstick-firewall.service', 'disable')
    else:
        return systemctl('lernstick-firewall.service', 'enable')

# escape characters in URLs for squid
def escape_url4squid(url):
    return url.translate(str.maketrans({
        ".":  r"\."
    }))

@arg_logger
def set_url_whitelist(url_whitelist):
    if url_whitelist != '':
        url_whitelist = escape_url4squid(url_whitelist)
        logger.debug(f"Appending whitelist with {url_whitelist}")
        url_whitelist = '\n' + url_whitelist + '\n'
        os.makedirs(f'{INITRD}/backup/etc/lernstick-firewall/proxy.d', exist_ok = True)
        r = helpers.file_put_contents(f'{INITRD}/newroot/etc/lernstick-firewall/proxy.d/glados.conf', url_whitelist, append = True) > 0
        r = r and helpers.file_put_contents(f'{INITRD}/newroot/etc/lernstick-firewall/url_whitelist', url_whitelist, append = True) > 0 # for backward compatibility, todo: remove as soon as possible
        return r

# Adds a polkit entry.
#
# @param string desc: A descriptionof the action.
# @param string identity: A semi-colon separated list of globs to match
#  identities. Each glob should start with unix-user: or unix-group: to specify
#  whether to match on a UNIX user name or a UNIX group name. Netgroups are
#  supported with the unix-netgroup: prefix, but cannot support glob syntax.
# @param string action: A semi-colon separated list of globs to match action
#  identifiers.
# @param string res_active: The result to return for subjects in an active local
#  session that matches one or more of the given identities. Allowed values are
#  similar to what can be used in the defaults section of .policy files used to
#  define actions, e.g. yes, no, auth_self, auth_self_keep, auth_admin and
#  auth_admin_keep.
# @param string res_inactive: Like ResultActive but instead applies to subjects
#  in inactive local sessions.
# @param string res_any: Like ResultActive but instead applies to any subject
# @see https://www.freedesktop.org/software/polkit/docs/0.105/pklocalauthority.8.html
#
@arg_logger
def add_polkit_entry(desc, identity, action, res_any, res_inactive, res_active, file):
    vals = ['yes', 'no', 'auth_self', 'auth_self_keep', 'auth_admin', 'auth_admin_keep']
    if res_active in vals and res_any in vals and res_inactive in vals:
        contents = textwrap.dedent(r"""
            [{desc}]
            Identity={identity}
            Action={action}
            ResultAny={res_any}
            ResultInactive={res_inactive}
            ResultActive={res_active}
        """)
        contents = contents.format(**locals())
        logger.debug(f"Adding entry to {file}:\n{contents}")
        r = helpers.file_put_contents(file, contents) > 0
    else:
        logger.warning(f"ResultAny, ResultInactive, ResultActive must be in {vals}")
        r=False
    if not r: logger.error(f'Creating polkit entry for "{desc}" failed')
    return r

# Returns an xpath to search for created from a dictionary d
def xml_get_xpath(d):
    xpath = '.'
    for tag, attribs in d.items():
        xpath += f'/{tag}'
        for attrib,value in attribs.items():
            xpath += f'[@{attrib}="{value}"]'
    return xpath

# Returns a attribute with namespace
def xml_get_nsattrib(attrib, nsmap):
    if ":" in attrib:
        ns, attrib = attrib.split(":", 1)
        attrib = etree.QName(nsmap[ns], attrib)
    return attrib

# Returns a dictionary of (possibly namespaced) attributes
def xml_get_attribs(attribs, nsmap):
    ret = {}
    for attrib, value in attribs.items():
        ret[xml_get_nsattrib(attrib, nsmap)] = value
    return ret

# Removes a tree of elements given by the dictionary d
def xml_remove_element_tree(d, root, nsmap):
    if (tag := root.find(xml_get_xpath(d), namespaces=nsmap)) is not None:
        tag.getparent().remove(tag)
        d.popitem()
        if d:
            return xml_remove_element_tree(d, root, nsmap)
    return root

# Alters an element text-value given by the dictionary d
def xml_change_element(d, value, root, nsmap):
    if (tag := root.find(xml_get_xpath(d), namespaces=nsmap)) is not None:
        tag.text = u"{}".format(value)
        return True
    else:
        return False

# Creates an element with text-value given by the dictionary d
def xml_create_element(d, value, root, nsmap):
    r = root
    for i, (tag, attribs) in enumerate(d.items()):
        r = etree.SubElement(r, tag, attrib=xml_get_attribs(attribs, nsmap))
        if i == 0:
            item = r
    r.text = u"{}".format(value)
    root.append(item)
    return root

def libreoffice(home, config):
    registry_file = f'{INITRD}/newroot/{home}/.config/libreoffice/4/user/registrymodifications.xcu'

    # setup the namespaces for the XML-file
    nsmap = {
        'oor': 'http://openoffice.org/2001/registry',
        'xs':  'http://www.w3.org/2001/XMLSchema',
        'xsi': 'http://www.w3.org/2001/XMLSchema-instance'
    }

    # define entries to change/create or remove

    #<item oor:path="/org.openoffice.Office.Common/Save/Document"><prop oor:name="AutoSave" oor:op="fuse"><value>false</value></prop></item>
    autoSave = {
        "item": {"oor:path": "/org.openoffice.Office.Common/Save/Document"},
        "prop": {"oor:name": "AutoSave", "oor:op": "fuse"},
        "value": {}
    }
    #<item oor:path="/org.openoffice.Office.Common/Save/Document"><prop oor:name="AutoSaveTimeIntervall" oor:op="fuse"><value>1</value></prop></item>
    autoSaveTimeIntervall = {
        "item": {"oor:path": "/org.openoffice.Office.Common/Save/Document"},
        "prop": {"oor:name": "AutoSaveTimeIntervall", "oor:op": "fuse"},
        "value": {}
    }
    #<item oor:path="/org.openoffice.Office.Common/Save/Document"><prop oor:name="CreateBackup" oor:op="fuse"><value>true</value></prop></item>
    createBackup = {
        "item": {"oor:path": "/org.openoffice.Office.Common/Save/Document"},
        "prop": {"oor:name": "createBackup", "oor:op": "fuse"},
        "value": {}
    }
    backupPath1 = {
        "item": {"oor:path": "/org.openoffice.Office.Common/Path/Current"},
        "prop": {"oor:name": "Backup", "oor:op": "fuse"},
        "value": {"xsi:nil": "true"}
    }
    backupPath2 = {
        "item": {"oor:path": "/org.openoffice.Office.Paths/Paths/org.openoffice.Office.Paths:NamedPath['Backup']"},
        "prop": {"oor:name": "WritePath", "oor:op": "fuse"},
        "value": {}
    }
    tmpPath1 = {
        "item": {"oor:path": "/org.openoffice.Office.Common/Path/Current"},
        "prop": {"oor:name": "Temp", "oor:op": "fuse"},
        "value": {"xsi:nil": "true"}
    }
    tmpPath2 = {
        "item": {"oor:path": "/org.openoffice.Office.Paths/Paths/org.openoffice.Office.Paths:NamedPath['Temp']"},
        "prop": {"oor:name": "WritePath", "oor:op": "fuse"},
        "value": {}
    }

    if os.path.exists(registry_file):
        logger.debug(f"{registry_file} exists; changing entries")
        copy(registry_file, f"{registry_file}.bak", fail_ok=True)
        tree = etree.parse(registry_file)
        root = tree.getroot()
        nsmap = root.nsmap
    else:
        logger.debug(f"{registry_file} does not exist; creating it")
        root = etree.Element(etree.QName(nsmap['oor'], 'items'), nsmap=nsmap)

    if not xml_change_element(autoSaveTimeIntervall, config['libre_autosave_interval'], root, nsmap):
        root = xml_create_element(autoSaveTimeIntervall, config['libre_autosave_interval'], root, nsmap)

    if not xml_change_element(autoSave, config['libre_autosave'], root, nsmap):
        root = xml_create_element(autoSave, config['libre_autosave'], root, nsmap)

    if not xml_change_element(createBackup, config['libre_createbackup'], root, nsmap):
        root = xml_create_element(createBackup, config['libre_createbackup'], root, nsmap)

    if config['libre_autosave_path'] != '':
        if not xml_change_element(backupPath1, '', root, nsmap):
            root = xml_create_element(backupPath1, '', root, nsmap)
        path = f"file://{config['libre_autosave_path']}"
        if not xml_change_element(backupPath2, path, root, nsmap):
            root = xml_create_element(backupPath2, path, root, nsmap)
    else:
        root = xml_remove_element_tree(backupPath1, root, nsmap)


    if config['libre_createbackup_path'] != '':
        if not xml_change_element(tmpPath1, '', root, nsmap):
            root = xml_create_element(tmpPath1, '', root, nsmap)
        path = f"file://{config['libre_createbackup_path']}"
        if not xml_change_element(tmpPath2, path, root, nsmap):
            root = xml_create_element(tmpPath2, path, root, nsmap)
    else:
        root = xml_remove_element_tree(tmpPath1, root, nsmap)

    # write the altered XML back
    os.makedirs(os.path.dirname(registry_file), exist_ok = True)
    etree.ElementTree(root).write(registry_file,
        encoding='UTF-8',
        xml_declaration=True,
        pretty_print=True
    )

    # These 2 crazy lines are needed, because, either etree or libreoffices xml
    # parser writes boolean wrong (!!). We change all booleans to lowercase
    # such that libreoffice is statisfied and autosave recovery still works. I
    # really hope to remove these 2 lines at some point, because this is
    # terrible, just terrible! I hate to write fixes like this.
    file_regex("\>True\<", ">true<", registry_file)
    file_regex("\>False\<", ">false<", registry_file)


@arg_logger
def screen_capture(enabled, config):
    if enabled and config['screen_capture_command'] != '':
        os.makedirs(f'{INITRD}/newroot/{config["screen_capture_path"]}', exist_ok = True)
        systemctl('screen_capture.service', 'enable')
        helpers.run(f'chroot {INITRD}/newroot ln -sf /var/log/screen_capture.log "{config["screen_capture_path"]}/screen_capture.log"')

        contents = textwrap.dedent(r"""
        # screen_capture
        name+=("screen_capture")
        threshold+=("{screen_capture_overflow_threshold}")
        path+=("{screen_capture_path}")
        hardlink+=("@(*.m3u8|*.log)")
        move+=("*.ts")
        remove+=("*.ts")
        log+=("screen_capture.log")
        chunk+=("{screen_capture_chunk}")
        """)

        helpers.file_put_contents(f'{INITRD}/newroot/etc/launch.conf', contents.format(**config), append = True)

@arg_logger
def keylogger(enabled, config):
    if enabled:
        os.makedirs(f'{INITRD}/newroot/{config["keylogger_path"]}', exist_ok = True)
        systemctl('keylogger.service', 'enable')

        contents = textwrap.dedent(r"""
        # keylogger
        name+=("keylogger")
        threshold+=("0m")
        path+=("{keylogger_path}")
        hardlink+=("")
        move+=("*.key")
        remove+=("")
        log+=("")
        chunk+=("10")
        """)

        helpers.file_put_contents(f'{INITRD}/newroot/etc/launch.conf', contents.format(**config), append = True)

##
# Events in audit_events are being logged. For all possible audit events:
# @see https://docs.python.org/3/library/audit_events.html#audit-events
# This will create for example a log entry of the form:
# 2022-09-02 15:28:28,583 - DEBUG - audit - event='os.chown' with args=('/path/to/file', 1000, 1000, -1) ...
##
def audit(event, args):
    for e in audit_events:
        if fnmatch.fnmatch(event, e):
            logger.debug(f'{event=} with {args=}')
            return


"""
The main function

@param Namespace args: arguments object from argparse
@param Namespace logger: logger instance
"""
def main(args, logger):
    logger.info('-'*20 + '[prepare]' + '-'*20)
    logger.info(f'Script launched with arguments: {sys.argv}')

    # enable auditting, when logging level is DEBUG
    if args.debug:
        sys.addaudithook(audit)

    env = {
        'DISPLAY': helpers.get_env("DISPLAY"),
        'XAUTHORITY': helpers.get_env("XAUTHORITY", uid = 1000),
    }

    logger.debug(f'Environment variables: {json.dumps(env, indent = 4)}')

    helpers.file_put_contents(f"{INITRD}/restore", "1")

    # put the token into the info file
    if os.path.isfile(infoFile) and os.access(infoFile, os.R_OK):
        helpers.file_put_contents(infoFile, f"\ntoken={args.token}\n", append = True)

    # read the variables from info file
    actionDownload = helpers.get_info("actionDownload", infoFile)
    actionFinish = helpers.get_info("actionFinish", infoFile)
    actionNotify = helpers.get_info("actionNotify", infoFile)
    actionMd5 = helpers.get_info("actionMd5", infoFile)
    actionConfig = helpers.get_info("actionConfig", infoFile)

    # construct the API URLs
    urlDownload = actionDownload.replace('{token}', args.token)
    urlFinish = actionFinish.replace('{token}', args.token)
    urlNotify = actionNotify.replace('{token}', args.token)
    urlMd5 = actionMd5.replace('{token}', args.token)
    urlConfig = actionConfig.replace('{token}', args.token)

    helpers.file_put_contents(infoFile, f'\nurlDownload="{urlDownload}"\nurlFinish="{urlFinish}"\nurlNotify="{urlNotify}"\nurlMd5="{urlMd5}"\nurlConfig="{urlConfig}"\n', append = True)

    get_config_from_server(urlConfig)

    # create necessary directory structure
    _, desktop = helpers.run(f"sudo -u {exam_user} xdg-user-dir DESKTOP")
    _, home = helpers.run(f"sudo -u {exam_user} xdg-user-dir")
    directory_structure = [
        {'mode': 0o755, 'dir': f"{INITRD}/backup"},
        {'mode': 0o755, 'dir': f"{INITRD}/base"},
        {'mode': 0o755, 'dir': f"{INITRD}/newroot"},
        {'mode': 0o755, 'dir': f"{INITRD}/squashfs"},
        {'mode': 0o755, 'dir': f"{INITRD}/exam"},
        {'mode': 0o755, 'dir': f"{INITRD}/tmpfs"},
        {'mode': 0o755, 'uid': root_uid, 'gid': root_gid, 'dir': f"{INITRD}/backup/etc/NetworkManager/system-connections"},
        {'mode': 0o755, 'uid': root_uid, 'gid': root_gid, 'dir': f"{INITRD}/backup/etc/NetworkManager/dispatcher.d"},
        {'mode': 0o755, 'uid': exam_uid, 'gid': exam_gid, 'dir': f"{INITRD}/backup/{desktop}/"},
        {'mode': 0o755, 'uid': exam_uid, 'gid': exam_gid, 'dir': f"{INITRD}/backup/{home}/.config"},
        {'mode': 0o755, 'uid': root_uid, 'gid': root_gid, 'dir': f"{INITRD}/backup/usr/bin/"},
        {'mode': 0o755, 'uid': root_uid, 'gid': root_gid, 'dir': f"{INITRD}/backup/usr/sbin/"},
        {'mode': 0o755, 'uid': root_uid, 'gid': root_gid, 'dir': f"{INITRD}/backup/etc/live/config/"},
        {'mode': 0o755, 'uid': root_uid, 'gid': root_gid, 'dir': f"{INITRD}/backup/etc/lernstick-firewall/"},
        {'mode': 0o755, 'uid': root_uid, 'gid': root_gid, 'dir': f"{INITRD}/backup/etc/avahi/"},
        {'mode': 0o700, 'uid': root_uid, 'gid': root_gid, 'dir': f"{INITRD}/backup/root"},
        {'mode': 0o700, 'uid': root_uid, 'gid': root_gid, 'dir': f"{INITRD}/backup/root/.ssh"},
        {'mode': 0o755, 'uid': root_uid, 'gid': root_gid, 'dir': f"{INITRD}/backup/usr/share/applications"}
    ]

    # set proper permissions
    for d in directory_structure:
        if 'mode' in d:
            os.makedirs(d['dir'], mode = d['mode'], exist_ok = True)
        else:
            os.makedirs(d['dir'], exist_ok = True)
        if 'uid' in d and 'gid' in d:
            os.chown(d['dir'], d['uid'], d['gid'])

    # os.chown(f"{INITRD}/backup/{desktop}/", exam_uid, exam_gid)
    # os.chown(f"{INITRD}/backup/{home}/", exam_uid, exam_gid)
    # os.chmod(f"{INITRD}/backup/root", 0o700)
    # os.chmod(f"{INITRD}/backup/root/.ssh", 0o700)

    # get all active network connections
    cenv = {**env, **{'LC_ALL': 'C'}} 
    _, connections = helpers.run("nmcli -t -f state,connection d status | awk -F: '$1==\"connected\"{print $2}'", env = cenv)
    for connection in connections.split("\n"):
        # set the autoconnect priority to 999
        helpers.run(f'nmcli connection modify "{connection}" connection.autoconnect-priority 999', env = cenv)

        # try to copy both files, the old one and the new one for backwards compatibility
        copy(f'{mnConnectionsPath}/{connection}', f"{INITRD}/backup/{mnConnectionsPath}/", fail_ok = True)
        copy(f'{mnConnectionsPath}/{connection}.nmconnection', f"{INITRD}/backup/{mnConnectionsPath}/", fail_ok = True)

    # edit copied connections manually, because nmcli will remove the wifi-sec.psk password when edited by nmcli modify
    #sed -i '/\[connection\]/a permissions=user:root:;' ${initrd}/backup/etc/NetworkManager/system-connections/*

    # copy needed scripts and files
    copy("/etc/NetworkManager/dispatcher.d/02searchExamServer", f"{INITRD}/backup/etc/NetworkManager/dispatcher.d/02searchExamServer")

    # those should be removed as fast as possible
    copy("/usr/bin/lernstick_backup", f"{INITRD}/backup/usr/bin/lernstick_backup") #@todo: remove
    copy("/usr/bin/lernstick_autostart", f"{INITRD}/backup/usr/bin/lernstick_autostart") #@todo: remove
    copy("/usr/sbin/lernstick-firewall", f"{INITRD}/backup/usr/sbin/lernstick-firewall") #@todo: remove
    copy("/etc/lernstick-firewall/lernstick-firewall.conf", f"{INITRD}/backup/etc/lernstick-firewall/lernstick-firewall.conf") #@todo: remove

    # config of /etc/lernstickWelcome
    copy("/etc/lernstickWelcome", f"{INITRD}/backup/etc/lernstickWelcome")
    file_regex("ShowNotUsedInfo=.*", "ShowNotUsedInfo=false", f"{INITRD}/backup/etc/lernstickWelcome")
    file_regex("AutoStartInstaller=.*", "AutoStartInstaller=false", f"{INITRD}/backup/etc/lernstickWelcome")

    # This is to fix an issue when the DNS name of the exam server ends in .local (which is the
    # case in most Microsoft domain environments). In case of a .local name the mDNS policy in
    # /etc/nsswitch.conf will catch. This ends in ssh login delays of up to 20 seconds. Changing it 
    # to .alocal is a workaround. Better is not to use mDNS in an exam.
    file_regex("#domain-name=local", "domain-name=.alocal", "/etc/avahi/avahi-daemon.conf",
        out_file = f"{INITRD}/backup/etc/avahi/avahi-daemon.conf")

    # mount/prepare the root filesystem
    mount_rootfs("newroot", home)

    # setup busybox
    _, output = helpers.run(f"/var/lib/lernstick-exam-client/setup_busybox.sh {INITRD}")

    # copy shutdown script, this script will be executed later by systemd-shutdown
    copy(f"{INITRD}/squashfs/mount.sh", "/lib/systemd/lernstick-shutdown")
    os.chmod("/lib/systemd/lernstick-shutdown", 0o755)

    # We do this here, anyway the script /lib/systemd/system-shutdown/lernstick might
    # also copy it. That's why in the above line the mount.sh script is also copied to
    # /lib/systemd/lernstick-shutdown. The above 2 lines are for backward compatibility.
    copy(f"{INITRD}/squashfs/mount.sh", f"{INITRD}/shutdown")
    os.chmod(f"{INITRD}/shutdown", 0o755)

    # remove policykit action for lernstick welcome application
    remove(f"{INITRD}/newroot/usr/share/polkit-1/actions/ch.lernstick.welcome.policy", fail_ok = True)

    # Allow user to Power off the system
    add_polkit_entry(desc="Allow user to Power off the system",
        identity="unix-user:user",
        action="org.freedesktop.login1.power-off",
        res_any="yes",
        res_inactive="yes",
        res_active="yes",
        file=f"{INITRD}/newroot/etc/polkit-1/localauthority/90-mandatory.d/10-shutdown.pkla")

    # add an entry to finish the exam in the dash
    add_dash_entry("finish_exam.desktop", env=env)

    # Welcome to exam .desktop entry to be executed at autostart
    os.makedirs(f"{INITRD}/newroot/etc/xdg/autostart/", exist_ok = True)
    os.makedirs(f"{INITRD}/newroot/usr/share/applications/", exist_ok = True)

    # add an entry to show information about the exam in the dash
    add_dash_entry("show-info.desktop", env=env)

    # Copy all dependencies, @todo: remove??
    if os.path.isdir("/var/lib/lernstick-exam-client/persistent/"):
        copytree("/var/lib/lernstick-exam-client/persistent/", f"{INITRD}/newroot/",
            copy_function = lambda src, dst: copy(src, dst, dry_run = False, follow_symlinks = False)
        )

    ###########################################
    # apply specific exam config if available #
    ###########################################

    get = lambda *args, **kwargs: helpers.get_config(*args, file = f'{INITRD}/config.json', **kwargs)

    logger.debug("setting expert_settings")
    allow_network_access(get('grp_netdev', default = False))
    allow_sudo(get('allow_sudo', default = False))
    allow_mount_external(get('allow_mount_external', default = False))
    allow_mount_system(get('allow_mount_system', default = False))
    firewall_off(get('firewall_off', default = False))

    # setup screenshots in the given interval
    if get('screenshots', default = False):
        systemctl('screenshot.service', 'enable')

    set_url_whitelist(get('url_whitelist', default = ''))

    # config->max_brightness
    if 0 <= get('max_brightness', default = 100) < 100:
        systemctl('max_brightness.service', 'enable')

    # set all libreoffice options
    libreoffice(home, config = {
        'libre_autosave':           get('libre_autosave', default = False),
        'libre_createbackup':       get('libre_createbackup', default = False),
        'libre_autosave_interval':  get('libre_autosave_interval', default = 10),
        'libre_autosave_path':      get('libre_autosave_path', default = ''),
        'libre_createbackup_path':  get('libre_createbackup_path', default = ''),
    })

    # set up the launch timer service if keylogger or screen_capture is active
    if os.path.exists(f"{INITRD}/newroot/etc/launch.conf"):
        os.remove(f"{INITRD}/newroot/etc/launch.conf")
    if get('screen_capture', default = False) or get('keylogger', default = False):
        # activate the timer unit
        helpers.run(f'chroot {INITRD}/newroot ln -sf /etc/systemd/system/launch.timer /etc/systemd/system/timers.target.wants/launch.timer')

    # set all screen_capture options
    screen_capture(get('screen_capture', default = False), config = {
        'screen_capture_chunk':              get('screen_capture_chunk', default = 10),
        'screen_capture_overflow_threshold': get('screen_capture_overflow_threshold', default = '500m'),
        'screen_capture_path':               get('screen_capture_path', default = '/home/user/ScreenCapture'),
        'screen_capture_command':            get('screen_capture_command', default = ''),
    })

    # set up the keylogger service
    keylogger(get('keylogger', default = False), config = {
        'keylogger_path': get('keylogger_path', default = '/home/user/ScreenCapture'),
    })

    # set up the agent
    if get('agent', default = False):
        systemctl('lernstick-exam-agent.service', 'enable')
        systemctl('lernstick-exam-tray.service', 'enable')

    # fix the permissions
    os.makedirs(f'{INITRD}/newroot/{home}/.config', mode=0o755, exist_ok = True)
    chown(f'{INITRD}/newroot/{home}/.config', exam_uid, exam_gid, recursive = True)

    # Copy the current locale and xdg-dir specs to the exam, such that the
    # exam has the same language and localized directory structure as the
    # starting Lernstick.
    copy(f"{home}/.config/user-dirs.dirs", f"{INITRD}/newroot/{home}/.config/user-dirs.dirs")
    copy(f"{home}/.config/user-dirs.locale", f"{INITRD}/newroot/{home}/.config/user-dirs.locale")
    os.environ["HOME"] = home
    xdg_keys = ["XDG_DESKTOP_DIR", "XDG_DOWNLOAD_DIR", "XDG_TEMPLATES_DIR", "XDG_PUBLICSHARE_DIR", "XDG_DOCUMENTS_DIR", "XDG_MUSIC_DIR", "XDG_PICTURES_DIR", "XDG_VIDEOS_DIR"]
    for key in xdg_keys:
        dir = helpers.get_info(key, f"{home}/.config/user-dirs.dirs")
        dir = f"{INITRD}/newroot/{home if dir is None else dir}"
        os.makedirs(dir, mode = 0o755, exist_ok = True)
        os.chown(dir, exam_uid, exam_gid)

    # Copy all dependencies, @todo: remove??
    if os.path.isdir("/var/lib/lernstick-exam-client/persistent/"):
        copytree("/var/lib/lernstick-exam-client/persistent/", f"{INITRD}/newroot/",
            copy_function = lambda src, dst: copy(src, dst, dry_run = False, follow_symlinks = False)
        )

    # hand over the ssh key from the exam server
    ssh_key = helpers.get_info("sshKey", infoFile)
    helpers.file_put_contents(f'{INITRD}/backup/root/.ssh/authorized_keys', '\n'+ssh_key, append = True)
    os.chmod(f"{INITRD}/backup/root/.ssh/authorized_keys", 0o600)
    
    # hand over open ports
    glados = {
        'gladosIp': helpers.get_info("gladosIp", infoFile),
        'gladosPort': helpers.get_info("gladosPort", infoFile),
        'gladosHost': helpers.get_info("gladosHost", infoFile),
        'gladosProto': helpers.get_info("gladosProto", infoFile)
    }

    helpers.file_put_contents(f'{INITRD}/backup/etc/lernstick-firewall/net_whitelist_input', '\ntcp {gladosIp} 22'.format(**glados), append = True)

    # hand over the url whitelist
    set_url_whitelist("{gladosProto}://{gladosHost}".format(**glados))
    set_url_whitelist("{gladosProto}://{gladosIp}".format(**glados))

    # hand over allowed ip/ports
    helpers.file_put_contents(f'{INITRD}/backup/etc/lernstick-firewall/net_whitelist', '\ntcp {gladosIp} {gladosPort}'.format(**glados), append = True)

    # unique all the lines
    helpers.unique_lines(f'{INITRD}/backup/etc/lernstick-firewall/proxy.d/glados.conf')
    helpers.unique_lines(f'{INITRD}/backup/etc/lernstick-firewall/net_whitelist_input')
    helpers.unique_lines(f'{INITRD}/backup/etc/lernstick-firewall/net_whitelist')
    # for backward compatibility, todo: remove as soon as possible
    helpers.unique_lines(f'{INITRD}/backup/etc/lernstick-firewall/url_whitelist')

    # setup the environment variables and start the screen with the setup done script
    helpers.run('screen -d -m /var/lib/lernstick-exam-client/setup_done.py {0}'.format('-d' if args.debug else ''), env = env)

    # exit successfully
    clean_exit('done.')


if __name__ == '__main__':
    # parse the command line arguments
    parser = argparse.ArgumentParser(
        prog = "prepare.py",
        description = "main entry point for client preparation"
    )

    required = parser.add_argument_group('required arguments')
    required.add_argument("--token", "-t",
        help = "current token",
        required = True
    )
    parser.add_argument('-d', '--debug',
        help = 'enable debugging (prints a lot of messages to stdout).',
        default = True,
        action = "store_true"
    )
    args = parser.parse_args()

    # setup logging
    logger = logging.getLogger("root") # create a logger called root
    logger.setLevel(logging.DEBUG if args.debug else logging.INFO)
    ch = logging.StreamHandler() # create console handler
    fh = logging.FileHandler(logFile) # create file handler
    ch.setFormatter(helpers.TerminalColorFormatter()) # formatter of console handler
    fh.setFormatter(helpers.FileFormatter()) # set formatter of file handler
    logger.addHandler(ch) # add handlers to logger
    logger.addHandler(fh)
    logging.captureWarnings(True) # also capture warnings from other libs

    try:
        main(args, logger)
    except Exception:
        logger.exception("Exception in main()")
        raise
