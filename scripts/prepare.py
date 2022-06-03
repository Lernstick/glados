#!/usr/bin/env python3
#
# This is the main entry point for the script that sets up the client system.
# All this is executed on the client system with root permissions.
#

import argparse
import sys
import os
import json
import logging
import shutil # shell commands like cp, ...
import requests # requests.get()
import fileinput # edit files inplace
import re
import pathlib

# append to the interpreter’s search path for modules
directory = "/var/lib/lernstick-exam-client/persistent/var/lib/lernstick-exam-client"
sys.path.append(directory)
import functions as helpers # 

#constants
logFile = "/var/log/searchExamServer.prepare.log"
INITRD = "/run/initramfs"
exam_user = "user"
exam_uid = 1000
exam_gid = 1000
infoFile = f"{INITRD}/info"
configFile = f"{INITRD}/config.json"
mountFile = f"{INITRD}/mount"
mnConnectionsPath = "/etc/NetworkManager/system-connections"

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
    logger.info('-'*40)

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

# retrieves the configuration json and stores it to $configFile
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
@param bool fail_ok: destination file
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
def file_regex(find, replace, in_file, out_file = None):
    in_context = fileinput.FileInput(in_file, backup = '.bak', inplace = out_file is None)
    out_context = open('/dev/null' if out_file is None else out_file, 'w')
    old = sys.stdout
    with in_context as in_fh, out_context as out_fh:
        sys.stdout = sys.stdout if out_file is None else out_fh
        for line in in_fh:
            if replace is not None:
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

def mount(args, src, dst = None, only_return_command = False):
    logger.debug(f"Mounting {src} -> {dst} using args {args}")
    cmd = f'mount {args} "{src}"' if dst is None else f'mount {args} "{src}" "{dst}"'
    if only_return_command:
        return cmd
    else:
        ret, out = helpers.run(cmd)
        return ret

def chown(path, uid, gid, recursive = False):
    logger.debug(f"changing owner of {path} to {uid}:{gid}, {recursive = }")
    if not recursive:
        os.chown(path, uid, gid)
    else:
        for root, dirs, files in os.walk(path):
            for obj in dirs+files:
                os.chown(os.path.join(root, obj), uid, gid)

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
def add_dash_entry(entry):
    logger.debug(f"adding dash entry for {entry}")

    # place entry in "favorite apps" of Gnome3's dash in the system-db
    gfav_apps = f'{INITRD}/newroot/etc/dconf/db/local.d/01-gnome-favorite-apps'
    if os.path.isfile(gfav_apps) and os.access(gfav_apps, os.R_OK):
        apps = extract(-1, 0, r'favorite-apps\=', gfav_apps, separator = r'\,|\[|\]|\s')[1:]
        apps = list(filter(None, apps)) # remove all empty ('') elements from list
    else:
        apps = []

    apps.append(f"'{entry}'") # add the entry to the list
    new_value = ", ".join(list(set(apps))) # unique the list and join them
    new_value = f'[org/gnome/shell]\nfavorite-apps=[{new_value}]\n'
    helpers.file_put_contents(gfav_apps, new_value)

    # this touch is needed, because dconf update is not rebuilding the database if the
    # directory containing the rules has the same mtime as before
    pathlib.Path(f"{INITRD}/newroot/etc/dconf/db/local.d").touch()
    helpers.run(f"chroot {INITRD}/newroot dconf update")

    # place ${entry} in "favorite apps" of Gnome3's dash in the user-db
    copy('/home/user/.config/dconf/user', '/home/user/.config/dconf/user.bak')
    if os.path.exists(f'{INITRD}/newroot/home/user/.config/dconf/user'):
        copy(f'{INITRD}/newroot/home/user/.config/dconf/user', '/home/user/.config/dconf/')
    helpers.run('sync')

    _, old_value = helpers.run('sudo -u user dconf read /org/gnome/shell/favorite-apps')
    if old_value == '':
        apps = []
    else:
        apps = re.split(r'\,|\[|\]|\s', old_value)
        apps = list(filter(None, apps)) # remove all empty ('') elements from list

    apps.append(f"'{entry}'")
    new_value = ", ".join(list(set(apps))) # unique the list and join them
    helpers.run(f'sudo -u user dconf write "/org/gnome/shell/favorite-apps" "[{new_value}]"; sync')

    dconf_dir = f'{INITRD}/newroot/home/user/.config/dconf/'
    if os.path.exists(dconf_dir):
        os.makedirs(dconf_dir, exist_ok = True)
    copy('/home/user/.config/dconf/user', f'{INITRD}/newroot/home/user/.config/dconf/user')
    copy('/home/user/.config/dconf/user.bak', '/home/user/.config/dconf/user')

def systemctl(service, state):
    r, _ = helpers.run(f'chroot {INITRD}/newroot systemctl {state} {service}')
    if r:
        logger.debug(f"Service {service} successfully set to {state}")
    else:
        logger.error(f"Setting {service} to {state} failed")
    return r

# config->grp_netdev
def allow_network_access(allowed):
    logger.debug(f"allowing network access -> {allowed}")
    if allowed:
        # add the user explicitely to the netdev group
        r, _ = helpers.run(f'chroot {INITRD}/newroot gpasswd -a user netdev')
    else:
        # remove user from the netdev group to prevent him from changing network connections
        r, _ = helpers.run(f'chroot {INITRD}/newroot gpasswd -d user netdev')
        file_regex('netdev', '', f'{INITRD}/newroot/etc/live/config.conf.d/user-setup.conf')
    return r

# config->allow_sudo
def allow_sudo(allowed):
    logger.debug(f"allowing sudo -> {allowed}")
    sudo_file = f'{INITRD}/newroot/etc/sudoers.d/01-lernstick-exam'
    helpers.file_put_contents(sudo_file, 'user ALL=(ALL) NOPASSWD: ALL' if allowed else '')

def allow_mount_external(allowed):
    logger.debug(f"allow mounting external media -> {allowed}")
    polkit_file = f"{INITRD}/newroot/etc/polkit-1/localauthority/90-mandatory.d/10-udisks2-mount.pkla"
    contents = [
        "[allow user mounting and unmounting of non-system devices with self authentication]",
        "Identity=unix-user:user",
        "Action=org.freedesktop.udisks2.filesystem-mount",
        "ResultAny={0}",
        "ResultInactive={0}",
        "ResultActive={0}"
    ]
    helpers.file_put_contents(polkit_file, "\n".join(contents).format('yes' if allowed else 'auth_admin'))

def allow_mount_system(allowed):
    logger.debug(f"allow mounting internal media -> {allowed}")
    polkit_file = f"{INITRD}/newroot/etc/polkit-1/localauthority/90-mandatory.d/10-udisks2-mount-system.pkla"
    contents = [
        "[allow user mounting and unmounting of system devices with self authentication]",
        "Identity=unix-user:user",
        "Action=org.freedesktop.udisks2.filesystem-mount-system",
        "ResultAny={0}",
        "ResultInactive={0}",
        "ResultActive={0}"
    ]
    helpers.file_put_contents(polkit_file, "\n".join(contents).format('yes' if allowed else 'auth_admin'))

# enable or disable the firewall
def firewall_off(off):
    logger.debug(f"firewall set to {'off' if off else 'on'}")
    if off:
        systemctl('lernstick-firewall.service', 'disable')
        helpers.run(f'chroot {INITRD}/newroot /lib/systemd/lernstick-firewall stop')
    else:
        systemctl('lernstick-firewall.service', 'enable')

def set_url_whitelist(url_whitelist):
    if url_whitelist != '':
        url_whitelist = re.sub(r'\.', r'\\\.', url_whitelist)
        os.makedirs(f'{INITRD}/backup/etc/lernstick-firewall/proxy.d', exist_ok = True)
        helpers.file_put_contents(f'{INITRD}/newroot/etc/lernstick-firewall/proxy.d/glados.conf', url_whitelist, append = True)
        helpers.file_put_contents(f'{INITRD}/newroot/etc/lernstick-firewall/url_whitelist', url_whitelist, append = True)# for backward compatibility, todo: remove as soon as possible

def libreoffice(home, config):
    registry_file = f'{INITRD}/newroot/${home}/.config/libreoffice/4/user/registrymodifications.xcu'
    registry = [
        r'<item oor:path="/org.openoffice.Office.Recovery/AutoSave"><prop oor:name="TimeIntervall" oor:op="fuse"><value>{libre_autosave_interval}</value></prop></item>',
        r'<item oor:path="/org.openoffice.Office.Recovery/AutoSave"><prop oor:name="Enabled" oor:op="fuse"><value>{libre_autosave}</value></prop></item>',
        r'<item oor:path="/org.openoffice.Office.Common/Save/Document"><prop oor:name="CreateBackup" oor:op="fuse"><value>{libre_createbackup}</value></prop></item>'
    ]

    if config['libre_autosave_path'] != '':
        registry.extend([
            r'<item oor:path="/org.openoffice.Office.Common/Path/Current"><prop oor:name="Temp" oor:op="fuse"><value xsi:nil="true"/></prop></item>',
            r'<item oor:path="/org.openoffice.Office.Paths/Paths/org.openoffice.Office.Paths:NamedPath['"'"'Temp'"'"']"><prop oor:name="WritePath" oor:op="fuse"><value>file://{libre_autosave_path}</value></prop></item>'
        ])

    if config['libre_createbackup_path'] != '':
        registry.extend([
            r'<item oor:path="/org.openoffice.Office.Common/Path/Current"><prop oor:name="Backup" oor:op="fuse"><value xsi:nil="true"/></prop></item>',
            r'<item oor:path="/org.openoffice.Office.Paths/Paths/org.openoffice.Office.Paths:NamedPath['"'"'Backup'"'"']"><prop oor:name="WritePath" oor:op="fuse"><value>file://{libre_createbackup_path}</value></prop></item>'
        ])

    registry.append('</oor:items>')
    
    # if the file exists, remove the line containing the xml entries
    if os.path.exists(registry_file):
        file_regex('org.openoffice.Office.Recovery/AutoSave.*TimeIntervall', None, registry_file)
        file_regex('org.openoffice.Office.Recovery/AutoSave.*Enabled', None, registry_file)
        file_regex('org.openoffice.Office.Common/Save/Document.*CreateBackup', None, registry_file)
        file_regex('org.openoffice.Office.Paths/Paths/org.openoffice.Office.Paths.*NamedPath.*Backup.*WritePath', None, registry_file)
        file_regex('org.openoffice.Office.Paths/Paths/org.openoffice.Office.Paths.*NamedPath.*Temp.*WritePath', None, registry_file)
        file_regex('org.openoffice.Office.Common/Path/Current.*Temp', None, registry_file)
        file_regex('org.openoffice.Office.Common/Path/Current.*Backup', None, registry_file)
        file_regex('</oor:items>', None, registry_file)
    else:
        os.makedirs(f'{INITRD}/newroot/${home}/.config/libreoffice/4/user', exist_ok = True)
        registry = [
            r'<?xml version="1.0" encoding="UTF-8"?>',
            r'<oor:items xmlns:oor="http://openoffice.org/2001/registry" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        ] + registry

    # append the xml entries to the file
    helpers.file_put_contents(registry_file, r"\n".join(registry).format(**config), append = True)

def screen_capture(enabled, config):
    if enabled and config['screen_capture_command'] != '':
        os.makedirs(f'{INITRD}/newroot/{config["screen_capture_path"]}', exist_ok = True)
        systemctl('screen_capture.service', 'enable')
        helpers.run(f'chroot {INITRD}/newroot ln -s /var/log/screen_capture.log "{config["screen_capture_path"]}/screen_capture.log"')
        contents = [
            r'# screen_capture',
            r'name+=("screen_capture")',
            r'threshold+=("{screen_capture_overflow_threshold}")',
            r'path+=("{screen_capture_path}")',
            r'hardlink+=("@(*.m3u8|*.log)")',
            r'move+=("*.ts")',
            r'remove+=("*.ts")',
            r'log+=("screen_capture.log")',
            r'chunk+=("{screen_capture_chunk}")'
        ]
        helpers.file_put_contents(f'{INITRD}/newroot/etc/launch.conf', r'\n'.join(contents).format(**config), append = True)

def keylogger(enabled, config):
    if enabled:
        os.makedirs(f'{INITRD}/newroot/{config["keylogger_path"]}', exist_ok = True)
        systemctl('keylogger.service', 'enable')
        contents = [
            r'# keylogger'
            r'name+=("keylogger")',
            r'threshold+=("0m")',
            r'path+=("{keylogger_path}")',
            r'hardlink+=("")',
            r'move+=("*.key")',
            r'remove+=("")',
            r'log+=("")',
            r'chunk+=("10")'
        ]
        helpers.file_put_contents(f'{INITRD}/newroot/etc/launch.conf', r'\n'.join(contents).format(**config), append = True)

if __name__ == '__main__':
    # parse the command line arguments
    parser = argparse.ArgumentParser(
        prog = "prepare.py",
        description = "main entry point for client preparation"
    )

    required = parser.add_argument_group('required arguments')
    required.add_argument("--token", "-t", help = "current token", required = True)
    parser.add_argument('-d', '--debug', help = 'enable debugging (prints a lot of messages to stdout).', default = True, action = "store_true" )
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

    logger.info('-'*40)
    logger.info(f'Script launched with arguments: {sys.argv}')

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

    helpers.file_put_contents(infoFile, f'urlDownload="{urlDownload}"\nurlFinish="{urlFinish}"\nurlNotify="{urlNotify}"\nurlMd5="{urlMd5}"\nurlConfig="{urlConfig}"\n', append = True)

    get_config_from_server(urlConfig)

    # create necessary directory structure
    _, desktop = helpers.run(f"sudo -u {exam_user} xdg-user-dir DESKTOP")
    _, home = helpers.run(f"sudo -u {exam_user} xdg-user-dir")
    directory_structure = [
        f"{INITRD}/backup",
        f"{INITRD}/base",
        f"{INITRD}/newroot",
        f"{INITRD}/squashfs",
        f"{INITRD}/exam",
        f"{INITRD}/tmpfs",
        f"{INITRD}/backup/etc/NetworkManager/system-connections",
        f"{INITRD}/backup/etc/NetworkManager/dispatcher.d",
        f"{INITRD}/backup/{desktop}/",
        f"{INITRD}/backup/usr/bin/",
        f"{INITRD}/backup/usr/sbin/",
        f"{INITRD}/backup/etc/live/config/",
        f"{INITRD}/backup/etc/lernstick-firewall/",
        f"{INITRD}/backup/etc/avahi/",
        f"{INITRD}/backup/root/.ssh",
        f"{INITRD}/backup/usr/share/applications"
    ]
    for directory in directory_structure:
        os.makedirs(directory, exist_ok = True)

    # set proper permissions
    os.chown(f"{INITRD}/backup/{desktop}/", exam_uid, exam_gid)
    os.chown(f"{INITRD}/backup/{home}/", exam_uid, exam_gid)
    os.chmod(f"{INITRD}/backup/root", 755)
    os.chmod(f"{INITRD}/backup/root/.ssh", 700)

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
    os.chmod("/lib/systemd/lernstick-shutdown", 755)

    # We do this here, anyway the script /lib/systemd/system-shutdown/lernstick might
    # also copy it. That's why in the above line the mount.sh script is also copied to
    # /lib/systemd/lernstick-shutdown. The above 2 lines are for backward compatibility.
    copy(f"{INITRD}/squashfs/mount.sh", f"{INITRD}/shutdown")
    os.chmod(f"{INITRD}/shutdown", 755)

    # remove policykit action for lernstick welcome application
    remove(f"{INITRD}/newroot/usr/share/polkit-1/actions/ch.lernstick.welcome.policy", fail_ok = True)

    # add an entry to finish the exam in the dash
    add_dash_entry("finish_exam.desktop")

    # Welcome to exam .desktop entry to be executed at autostart
    os.makedirs(f"{INITRD}/newroot/etc/xdg/autostart/", exist_ok = True)
    os.makedirs(f"{INITRD}/newroot/usr/share/applications/", exist_ok = True)

    # add an entry to show information about the exam in the dash
    add_dash_entry("show-info.desktop")

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
        helpers.run(f'chroot {INITRD}/newroot ln -s /etc/systemd/system/launch.timer /etc/systemd/system/timers.target.wants/launch.timer')

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
    chown(f'{INITRD}/newroot/${home}/.config', exam_uid, exam_gid, recursive = True)

    # Copy all dependencies, @todo: remove??
    if os.path.isdir("/var/lib/lernstick-exam-client/persistent/"):
        copytree("/var/lib/lernstick-exam-client/persistent/", f"{INITRD}/newroot/",
            copy_function = lambda src, dst: copy(src, dst, dry_run = False, follow_symlinks = False)
        )

    # hand over the ssh key from the exam server
    ssh_key = helpers.get_info("sshKey", infoFile)
    helpers.file_put_contents(f'{INITRD}/backup/root/.ssh/authorized_keys', ssh_key, append = True)
    
    # hand over open ports
    glados = {
        'gladosIp': helpers.get_info("gladosIp", infoFile),
        'gladosPort': helpers.get_info("gladosPort", infoFile),
        'gladosHost': helpers.get_info("gladosHost", infoFile),
        'gladosProto': helpers.get_info("gladosProto", infoFile)
    }
    glados_sanitized = {k: re.sub(r'\.', r'\\\.', v) for k,v in glados.items()}
    helpers.file_put_contents(f'{INITRD}/backup/etc/lernstick-firewall/net_whitelist_input', 'tcp ${gladosIp} 22'.format(**glados), append = True)

    # hand over the url whitelist
    os.makedirs(f'{INITRD}/backup/etc/lernstick-firewall/proxy.d', exist_ok = True)
    host = "{gladosProto}://{gladosHost}".format(**glados_sanitized)
    ip = "{gladosProto}://{gladosIp}".format(**glados_sanitized)
    helpers.file_put_contents(f'{INITRD}/backup/etc/lernstick-firewall/proxy.d/glados.conf', host, append = True)
    helpers.file_put_contents(f'{INITRD}/backup/etc/lernstick-firewall/proxy.d/glados.conf', ip, append = True)
    
    # for backward compatibility, todo: remove as soon as possible
    helpers.file_put_contents(f'{INITRD}/backup/etc/lernstick-firewall/url_whitelist', host, append = True)
    helpers.file_put_contents(f'{INITRD}/backup/etc/lernstick-firewall/url_whitelist', ip, append = True)

    # hand over allowed ip/ports
    helpers.file_put_contents(f'{INITRD}/backup/etc/lernstick-firewall/net_whitelist', 'tcp ${gladosIp} ${gladosPort}'.format(**glados), append = True)

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