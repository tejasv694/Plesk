# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
import pmm_config
import pmmcli_config
from error_reporter import ErrorReporter
from pmm_dump_formatter import DumpSpecificationFormatter
import pmm_task
import pmm_suspend_handler
import pmm_repository_access_service
import errno
import osutil
import dirutil
import os
import sys
import time
import random
import datetime
import smtplib
import plesk_config
import socket

try: import subprocess
except ImportError: import compat.subprocess as subprocess

from email.Header import Header
from email.MIMEMultipart import MIMEMultipart
from email.MIMEText import MIMEText
from email.Utils import formatdate, parseaddr, formataddr
import re
import cPickle
import stacktrace
import codecs


def make_xlat(*args, **kwds):
    adict = dict(*args, **kwds)
    rx = re.compile('|'.join(map(re.escape, adict)))
    def one_xlat(match):
        return adict[match.group(0)]
    def xlat(text):
        return rx.sub(one_xlat, text)
    return xlat


actions = None


mswindows = (sys.platform == "win32")


#
# Daemon task processor class
#
class DaemonTasks:
    def __init__(self):
        self.__daemontasks = []
        self.__initialize()

    def __initialize(self):
        # get daemontasks
        daemontasks_dir = pmm_config.daemontasks_dir()
        if os.path.isdir(daemontasks_dir):
            names = os.listdir(daemontasks_dir)
            for name in names:
                fullname = os.path.join(daemontasks_dir, name)
                if os.path.isfile(fullname):
                    daemontask = DaemonTask.load(fullname)
                    if isinstance(daemontask,DaemonTask):
                        # add daemontask to daemontask actions
                        self.__daemontasks.append(daemontask)

    def get_daemontask_actions(self):
        return [daemontask.action() for daemontask in self.__daemontasks]

#
# Base daemon task class
# Daemon tasks are created with pmmcli then readed out with pmmcli-daemon
#
class DaemonTask:
    def __init__(self):
        self.__daemontask_filename = None
        pass

    def daemontask_filename(self):
        return self.__daemontask_filename

    def save(self):
        daemontasks_dir = pmm_config.daemontasks_dir()
        if not os.path.isdir(daemontasks_dir):
            dirutil.mkdirs(daemontasks_dir, 0750)
        daemontask_filename = os.path.join(daemontasks_dir,time.strftime('%Y%m%d%H%M%S',time.localtime()) + str(random.randint(0,1000)))
        while os.path.isfile(daemontask_filename):
            daemontask_filename = os.path.join(daemontasks_dir,time.strftime('%Y%m%d%H%M%S',time.localtime()) + str(random.randint(0,1000)))
        daemontask_file = open(daemontask_filename, 'w')
        cPickle.dump(self, daemontask_file)
        daemontask_file.close()

    def load(daemontask_filename):
        daemontask_file = open(daemontask_filename, 'r')
        daemontask = cPickle.load(daemontask_file)
        daemontask_file.close()
        daemontask.__daemontask_filename = daemontask_filename
        return daemontask

    load = staticmethod(load)

    def action(self):
        return DaemonTaskAction(self.__daemontask_filename)


#
# Daemon task class used with --export-dump-as-file
#
class DaemonTaskDeleteTempDump(DaemonTask):
    def __init__(self, dump_specification_formatter):
        self.__dump_specification_formatter = dump_specification_formatter

    def action(self):
        return DaemonTaskDeleteTempDumpAction(self.daemontask_filename(), self.__dump_specification_formatter)


class ActionItem:
    def process(self,logger):
        logger("Action " + self.__class__.__name__ + " started")


class DaemonTaskAction(ActionItem):
    def __init__(self, daemontask_filename):
        self.__daemontask_filename = daemontask_filename

    def action(self):
        return ActionItem()


class DeleteDumpAction(DaemonTaskAction):
    def __init__(self, dump_specification_formatter):
        self.__dump_specification_formatter = dump_specification_formatter

    def process(self,logger):
        ActionItem.process(self,logger)
        logger("Action param: %s:\n%s" % ('dump_specification_formatter',self.__dump_specification_formatter.buildString()))
        
        # remove dump with pmm-ras
        errcode = 0
        message = None
        logger("Delete temporary dump")
        access_service = pmm_repository_access_service.LocalRepositoryAccessService
        dumps_storage_credentials = self.__dump_specification_formatter.get_dumps_storage_credentials_formatter()
        errcode, message = access_service(dumps_storage_credentials).delete_dump(self.__dump_specification_formatter.buildXml())
        logger("Action ended with errcode=%d" % errcode)
        if message:
            logger("Message is: %s" % message)
        return True


class DaemonTaskDeleteTempDumpAction(DaemonTaskAction):
    def __init__(self, daemontask_filename, dump_specification_formatter):
        self.__daemontask_filename = daemontask_filename
        self.__dump_specification_formatter = dump_specification_formatter

    def process(self,logger):
        ActionItem.process(self,logger)
        logger("Action param: %s:\n%s" % ('dump_specification_formatter',self.__dump_specification_formatter.buildString()))
        
        errcode = 0
        message = None
        file = self.__dump_specification_formatter.get_destination_file()
        delta = datetime.datetime.now() - datetime.datetime.fromtimestamp( os.stat(file).st_atime )
        keep_temp_dump = pmmcli_config.get().get_keep_temp_dump()
        if delta.days > 0 or delta.seconds > keep_temp_dump:
            logger("Delete temporary dump file %s" % file)
            osutil.unlink_nothrow(file)
            logger("Delete daemon task file %s" % self.__daemontask_filename)
            osutil.unlink_nothrow(self.__daemontask_filename)
            return True
        else:
            logger("Delay deleting temporary dump file %s" % file)
            return False


class CleanTaskSessionAction(ActionItem):
    def __init__(self, task_id):
        self.__task_id = task_id

    def process(self,logger):
        ActionItem.process(self,logger)
        logger("Action param: %s:%s" % ('task_id',self.__task_id))
        task_manager = pmm_task.getPMMTaskManager()
        task_manager.removeTask(self.__task_id)


class CleanOrphanedSessionAction(ActionItem):
    def __init__(self, session_path):
        self.__session_path = session_path

    def process(self,logger):
        ActionItem.process(self,logger)
        logger("Action param: %s:%s" % ('session_path',self.__session_path))
        osutil.unlink_recursively(self.__session_path)

class DeleteActionLogAction(ActionItem):
    def __init__(self, logs_dir):
        self.__logs_dir = logs_dir

    def process(self, logger):
        ActionItem.process(self, logger)
        logger("Delete action log: %s" % (self.__logs_dir,))
        osutil.unlink_recursively(self.__logs_dir)

mailer = None


class Mailer:
    def __init__(self):
        #get init params from pmmcli_config file
        self.__smtpserver = pmmcli_config.get().get_smtpserver()
        self.__authrequired = pmmcli_config.get().get_authrequired()
        self.__smtpuser = pmmcli_config.get().get_smtpuser()
        self.__smtppass = pmmcli_config.get().get_smtppass()
        self.__from = pmmcli_config.get().get_notification_mail_from()
        self.__reply_to = pmmcli_config.get().get_notification_mail_replyto()

    def send(self, logger, mail_to, task_type, task_status, owner_guid, owner_type, owner_name, creation_date, fullname, logfile, topobject_id, topobject_type, topobject_name):
        # replace tokens with actual values
        status = "working"
        if task_status.get_stopped():
            status = 'stopped'
        elif task_status.get_finished():
            status = task_status.get_finished().get_status()

        if 'server' == owner_type and 'server' == owner_name:
            owner_name = pmmcli_config.get().get_notification_mail_panel_administrator_name()
        if 'server' == topobject_type and 'server' == topobject_name:
            topobject_name = pmmcli_config.get().get_notification_mail_panel_name()

        subject = pmmcli_config.get().get_notification_mail_subject_backup() if 'Backup' == task_type else pmmcli_config.get().get_notification_mail_subject_restore()

        with open(os.path.join(pmm_config.plesk_logs_directory(), 'mail_notifications_stat.log'), 'a+t') as f:
            f.write("%s;backup;%s\n" % (datetime.datetime.utcnow().isoformat('T'), subject))

        adict = {
            "%TASK_TYPE%": task_type
            , "%TASK_STATUS%": status
            , "%OWNER_GUID%": owner_guid
            , "%OWNER_TYPE%": owner_type
            , "%OWNER_NAME%": owner_name
            , "%CREATION_DATE%": creation_date
            , "%FULL_NAME%": fullname
            , "%HOST_NAME%": socket.gethostname()
            , "%TOPOBJECT_ID%": topobject_id
            , "%TOPOBJECT_TYPE%": topobject_type
            , "%TOPOBJECT_NAME%": topobject_name
        }
        translate = make_xlat(adict)
        
        # prepare message body
        body = pmmcli_config.get().get_notification_mail_body_backup() if 'Backup' == task_type else pmmcli_config.get().get_notification_mail_body_restore()
        body = body.replace('\\n','\r\n')
        body = translate(body)

        mail_from = self.__from
        if mail_from == '':
            if self.__authrequired == 0:
                mail_from = "PMMCli-Daemon<%s>" % mail_to
            else:
                mail_from = self.__smtpuser

        if self.__smtpserver != 'localhost' or self.__authrequired == 1:
            msg = self._createMessage(mail_from, mail_to, self.__reply_to, subject, body, logfile)
            smtp_server_session = smtplib.SMTP(self.__smtpserver)
            try:
                if self.__authrequired:
                    smtp_server_session.login(self.__smtpuser.encode('utf-8'), self.__smtppass.encode('utf-8'))

                smtp_result = smtp_server_session.sendmail(mail_from,mail_to,msg.as_string())
                if not smtp_result:
                    logger("E-mail sent to: " + mail_to)
                else:
                    for recip in smtp_result.keys():
                        errstr = """Could not delivery mail to: %s\nServer response is: %s\n%s""" % (recip, smtp_result[recip][0], smtp_result[recip][1])
                        logger(errstr)
            
            except smtplib.SMTPException, ex:
                errstr = """Could not delivery mail to: %s\nException %s raised : %s""" % (mail_to, repr(ex), str(ex))
                logger(errstr)
                pass
        else:
            # could not use smtplib interface on linux without authorization
            # use sendmail interface
            tmp_file = None
            tmp_file_attach = None
            try:
                if mswindows:
                    cmd = [os.path.join(plesk_config.get('PRODUCT_ROOT_D'), "admin", "bin", "plesk_sendmail.exe")]
                    cmd.append("--send")
                    (mail_from_name, mail_from_addr) = parseaddr(mail_from)
                    cmd.append("--from=" + str(Header(mail_from_addr)))
                    cmd.append("--from-name=" + str(Header(mail_from_name)))
                    cmd.append("--to=" + str(Header(mail_to)))
                    cmd.append("--to-name=" + str(Header(mail_to)))
                    if self.__reply_to:
                        (reply_to_name, reply_to_addr) = parseaddr(self.__reply_to)
                        cmd.append("--reply-to=" + str(Header(reply_to_addr)))
                    cmd.append("--subject=" + str(Header(subject)))
                    tmp_file = os.path.join(pmm_config.tmp_directory(), "pmmcli_daemon_mail_" + str(random.randint(0, 1000)))
                    with codecs.open(tmp_file, 'w', encoding='utf-8') as f:
                        f.write(body)
                    logger("tmp_file %s" % tmp_file)

                    cmd.append("--body=" + tmp_file)
                    if os.path.isfile(logfile):
                        tmp_file_attach = os.path.join(pmm_config.tmp_directory(), "pmmcli_daemon_mail_attach" + str(random.randint(0, 1000)))
                        with open(tmp_file_attach, 'wt') as f:
                            f.write(logfile)
                        cmd.append("--attachments=" + tmp_file_attach)

                    sendmail = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                    (sendmail_stdout, sendmail_stderr) = sendmail.communicate()
                    if 0 != sendmail.returncode:
                        ErrorReporter.sendNonzeroExitError(' '.join(cmd), sendmail.returncode, sendmail_stdout, sendmail_stderr)
                    logger("email sent")
                else:
                    msg = self._createMessage(mail_from, mail_to, self.__reply_to, subject, body, logfile)
                    mail_sender = "-f%s" % mail_from
                    cmd = ["/usr/sbin/sendmail", mail_sender, "-t", "-oi"]
                    sendmail = subprocess.Popen(cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                    (sendmail_stdout, sendmail_stderr) = sendmail.communicate(input=msg.as_string())
                    if 0 != sendmail.returncode:
                        ErrorReporter.sendNonzeroExitError(' '.join(cmd), sendmail.returncode, sendmail_stdout, sendmail_stderr)
                    logger("email sent: %s" % msg.as_string())

                logger("sendmail output: %s" % sendmail_stdout)
            finally:
                if mswindows:
                    if tmp_file:
                        os.unlink(tmp_file)
                    if tmp_file_attach:
                        os.unlink(tmp_file_attach)

    def _createMessage(self, mail_from, mail_to, reply_to, subject, body, logfile):
        msg = MIMEMultipart()
        (mail_from_name, mail_from_addr) = parseaddr(mail_from)
        msg['From'] = formataddr((str(Header(mail_from_name)), mail_from_addr))
        msg['To'] = mail_to
        if reply_to:
            (reply_to_name, reply_to_addr) = parseaddr(reply_to)
            msg['Reply-To'] = formataddr((str(Header(reply_to_name)), reply_to_addr))
        msg['Date'] = formatdate(localtime=True)
        msg['Subject'] = subject

        msg.attach(MIMEText(body, 'plain', 'utf-8'))

        if os.path.isfile(logfile):
            part = MIMEText(open(logfile,'rb').read(), 'xml', 'utf-8')
            part.add_header('Content-Disposition','attachment; filename="%s"' % os.path.split(logfile)[1])
            msg.attach(part)
        return msg

    def get():
        global mailer
        if not mailer:
            mailer = Mailer()
        return mailer

    get = staticmethod(get)


class MailToAction(ActionItem):
    def __init__(self, task_id, task_type, task_status, owner_guid, owner_type, owner_name, creation_date, fullname, logfilename, mail_to, topobject_id, topobject_type, topobject_name):
        self.__task_id = task_id
        self.__task_type = task_type
        self.__task_status = task_status
        self.__owner_guid = owner_guid
        self.__owner_type = owner_type
        self.__owner_name = owner_name
        self.__creation_date = creation_date
        self.__fullname = fullname
        self.__logfilename = logfilename
        self.__mail_to = mail_to
        self.__topobject_id = topobject_id
        self.__topobject_type = topobject_type
        self.__topobject_name = topobject_name
        self.__AUTHREQUIRED = 0
        self.__smtpserver = 'localhost'
        self.__smtpuser = ''
        self.__smtppass = ''

    def process(self,logger):
        ActionItem.process(self,logger)
        logger("Action params: %s:%s, %s:%s, %s:%s, %s:%s, %s:%s, %s:%s, %s:%s, %s:%s, %s:%s, %s:%s, %s:%s" % (
            'task_type', self.__task_type
            , 'owner_guid', self.__owner_guid
            , 'owner_type', self.__owner_type
            , 'owner_name', self.__owner_name
            , 'creation_date', self.__creation_date
            , 'fullname', self.__fullname
            , 'logfilename', self.__logfilename
            , 'mail_to', self.__mail_to
            , 'topobject_id', self.__topobject_id
            , 'topobject_type', self.__topobject_type
            , 'topobject_name', self.__topobject_name
        ))
        mailsender = Mailer().get()
        mailsender.send(
            logger
            , self.__mail_to
            , self.__task_type
            , self.__task_status
            , self.__owner_guid
            , self.__owner_type
            , self.__owner_name
            , self.__creation_date
            , self.__fullname
            , self.__logfilename
            , self.__topobject_id
            , self.__topobject_type
            , self.__topobject_name
        )
        # mark task as 'mailsent'
        storage = pmm_task.PMMTaskPersistentStorage()
        task = storage.getTask(self.__task_id)
        if task is not None:
            task.set('mailsent',True)
            pmm_task.PMMTaskManager().updateTask(task)


class UnsuspendTaskAction(ActionItem):
    def __init__(self, session_id):
        self.__session_id = session_id

    def process(self,logger):
        ActionItem.process(self,logger)
        logger("Action param: %s:%s" % ('session_id',self.__session_id))
        pmm_suspend_handler.SuspendHandler.unsuspend_all(self.__session_id, logger)


class PmmcliLogRotateAction(ActionItem):
    def __init__(self, file_name, last_rotation_time):
        self.__file_name = file_name
        self.__max_files = pmmcli_config.get().get_max_log_files()
        self.__max_size = pmmcli_config.get().get_max_log_size()
        self.__last_rotation_time = last_rotation_time

    def __rotate(self, logger):
        startswith = self.__file_name
        startswith_len = len(self.__file_name)
        files_to_delete = []
        for filename in os.listdir(pmm_config.pmm_logs_directory()):
            if not os.path.isfile(os.path.join(pmm_config.pmm_logs_directory(), filename)):
                continue
            if not filename.startswith(startswith):
                continue
            time_stamp = filename[startswith_len + 1:]
            if '' == time_stamp:
                continue
            creation_date = time.strptime(time_stamp,'%Y-%m-%d-%H%M%S')
            creation_datetime = datetime.datetime(creation_date[0], creation_date[1], creation_date[2])
            if (datetime.datetime.now() - creation_datetime).days > self.__max_files:
                files_to_delete.append(filename)
        for file_to_delete in files_to_delete:
            logger("delete old log file " + file_to_delete)
            osutil.unlink_nothrow(os.path.join(pmm_config.pmm_logs_directory(), file_to_delete))

        time_stamp = time.strftime('%Y-%m-%d-%H%M%S', time.localtime())
        rotated_filename = os.path.join(pmm_config.pmm_logs_directory(), self.__file_name) + "." + time_stamp
        logger('rename log file to ' + rotated_filename)
        os.rename(os.path.join(pmm_config.pmm_logs_directory(), self.__file_name), rotated_filename)

        self.update_last_rotation_time(logger)

    def process(self, logger):
        ActionItem.process(self, logger)
        logger("Action param: %s: %s" % ('file_name', self.__file_name))
        fullname = os.path.join(pmm_config.pmm_logs_directory(), self.__file_name)
        try:
            stat = os.stat(fullname)
        except OSError as e:
            if errno.ENOENT != e.errno:
                logger('Failed to stat the file: %s: %s' % (fullname, str(e)))
            return
        if stat.st_size >= self.__max_size or (datetime.datetime.now() - self.__last_rotation_time).days >= 1:
            self.__rotate(logger)

    @staticmethod
    def update_last_rotation_time(logger):
        last_rotation_time_path = pmm_config.pmm_logs_last_rotation_time_file()
        try:
            with open(last_rotation_time_path, 'wt') as last_rotation_time_file:
                last_rotation_time_file.write(datetime.datetime.now().strftime('%Y-%m-%d-%H%M%S'))
        except OSError as e:
            logger('Failed to write the file: %s: %s' % (last_rotation_time_path, str(e)))

    @staticmethod
    def get_last_rotation_time(logger):
        last_rotation_time_path = pmm_config.pmm_logs_last_rotation_time_file()
        try:
            with open(last_rotation_time_path, 'rt') as last_rotation_time_file:
                return datetime.datetime.strptime(last_rotation_time_file.read(), '%Y-%m-%d-%H%M%S')
        except IOError as e:
            if errno.ENOENT != e.errno:
                logger('Failed to read the file: %s: %s' % (last_rotation_time_path, str(e)))
        except ValueError as e:
            logger('Failed to parse the last rotation time: %s' % str(e))
        except Exception as e:
            logger('Failed to get the last rotation time: %s' % str(e))
        return datetime.datetime.fromtimestamp(0)

class SessionCleaner:
    def __init__(self):
        self.__task_sessions = []
        self.__orphaned_sessions = []
        self.__days_to_keep_sessions = pmmcli_config.get().get_days_to_keep_sessions()
        self.__initialize()

    def __initialize(self):
        session_paths = []
        task_manager = pmm_task.getPMMTaskManager()
        backup_task_list = task_manager.realGetTaskList(['Backup'])
        for task in backup_task_list:
            session_paths.append(task.get('session_path'))
            time_corrupted = False
            try:
                creation_datetime = datetime.datetime.fromtimestamp(task.get_creation_date())
            except ValueError:
                # task creation date is corrupted - add task to the task list to clear
                time_corrupted = True
            if time_corrupted:
                self.__task_sessions.append(task.get_task_id())
            else:
                delta = datetime.datetime.now() - creation_datetime
                if delta.days >= self.__days_to_keep_sessions:
                    self.__task_sessions.append(task.get_task_id())

        # find orphaned sessions
        sessions_directory = pmm_config.session_dir()
        if os.path.isdir(sessions_directory):
            names = os.listdir(sessions_directory)
            for name in names:
                fullname = os.path.join(sessions_directory, name)
                if os.path.isdir(fullname):
                    if fullname not in session_paths:
                        mod_time = datetime.datetime.fromtimestamp(os.path.getmtime(fullname))
                        delta = datetime.datetime.now() - mod_time
                        if delta.days >= self.__days_to_keep_sessions:
                            self.__orphaned_sessions.append(fullname)

    def get_task_sessions(self):
        return self.__task_sessions

    def get_orphaned_sessions(self):
        return self.__orphaned_sessions


class RestoreSessionCleaner:
    def __init__(self):
        self.__task_sessions = []
        self.__orphaned_sessions = []
        self.__days_to_keep_sessions = pmmcli_config.get().get_days_to_keep_sessions()
        self.__initialize()

    def __initialize(self):
        session_paths = []
        # fill in restore sessions accessible with pmm tasks
        task_manager = pmm_task.getPMMTaskManager()
        restore_task_list = task_manager.realGetTaskList(['Restore'])
        for task in restore_task_list:
            session_paths.append(task.get('session_path'))
            time_corrupted = False
            try:
                creation_datetime = datetime.datetime.fromtimestamp(task.get_creation_date())
            except ValueError:
                # task creation date is corrupted - add task to the task list to clear
                time_corrupted = True
            if time_corrupted:
                self.__task_sessions.append(task.get_task_id())
            else:
                delta = datetime.datetime.now() - creation_datetime
                if delta.days >= self.__days_to_keep_sessions:
                    self.__task_sessions.append(task.get_task_id())
        
        # find orphaned restore sessions
        restore_sessions_directory = pmm_config.restore_session_dir()
        if os.path.isdir(restore_sessions_directory):
            names = os.listdir(restore_sessions_directory)
            for name in names:
                fullname = os.path.join(restore_sessions_directory, name)
                if os.path.isdir(fullname):
                    if fullname not in session_paths:
                        # this is not finished restore session
                        mod_time = datetime.datetime.fromtimestamp(os.path.getmtime(fullname))
                        delta = datetime.datetime.now() - mod_time
                        if delta.days >= self.__days_to_keep_sessions:
                            self.__orphaned_sessions.append(fullname)

    def get_task_sessions(self):
        return self.__task_sessions

    def get_orphaned_sessions(self):
        return self.__orphaned_sessions

def get_obsolete_logs(log):
    re_name = re.compile(r'^(?:import|export|backup|restore|migration)-(\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2})-\d{3}$')

    logs_root = pmm_config.pmm_logs_directory()
    max_age = pmmcli_config.get().get_days_to_keep_sessions()
    max_count = pmmcli_config.get().get_max_number_of_log_dirs()
    now = datetime.datetime.now()
    log("Max logs age: %d days, max logs count: %d, now: %s" % (max_age, max_count, now))

    matched_dirs = [
        (name, datetime.datetime.strptime(m.group(1), '%Y-%m-%d-%H-%M-%S'))
        for name, m in ((name, re_name.match(name)) for name in os.listdir(logs_root))
        if m and os.path.isdir(os.path.join(logs_root, name))
    ]
    log("Matched dirs: %r" % matched_dirs)
    ordered_dirs = enumerate(sorted(matched_dirs, key=lambda e: e[1], reverse=True))
    return [
        os.path.join(logs_root, name)
        for i, (name, timestamp) in ordered_dirs
        if i >= max_count or (now - timestamp).days >= max_age
    ]

class ActionItems:
    def __init__(self, log):
        self.__logger = log
        self.__action_items = []
        self.__initialize()

    def __initialize(self):
        
        # Get restore tasks list
        task_manager = pmm_task.getPMMTaskManager()
        restore_task_list = task_manager.realGetTaskList(['Deploy'])
        for task in restore_task_list:
            if isinstance(task,pmm_task.DeployTask):
                task_status = task_manager.operatorGetTaskStatus(task)
                working = task_status.get_working()
                if working is None:
                    mailto = task.get('mailto')
                    mailsent = task.get('mailsent')
                    if not mailsent and mailto is not None and mailto != '':
                        self.__logger("MailToAction added")
                        self.__action_items.append(MailToAction(
                            task.get_task_id()
                            , 'Restore'
                            , task_status
                            , task.get('owner_guid')
                            , task.get('owner_type')
                            , task.get('owner_name')
                            , task.get_creation_date_formatted()
                            , task.get('fullname')
                            , task.get('migration_result_filename')
                            , mailto
                            , task.get('topobject_id')
                            , task.get('topobject_type')
                            , task.get('topobject_name')
                        ))
                    finished = task_status.get_finished()
                    if finished is not None and finished.get_status() != 'success':
                        self.__logger("UnsuspendTaskAction added")
                        self.__action_items.append(UnsuspendTaskAction(task.get('session_path')))
                    if finished is not None and task.get('delete_dump') == 'True':
                        self.__logger("DeleteDumpAction added")
                        dump_specification = DumpSpecificationFormatter(task.get('dump_specification'))
                        self.__action_items.append(DeleteDumpAction(dump_specification))

        
        # Get backup tasks list
        backup_task_list = task_manager.realGetTaskList(['Backup'])
        for task in backup_task_list:
            if isinstance(task,pmm_task.BackupTask):
                task_status = task_manager.operatorGetTaskStatus(task)
                working = task_status.get_working()
                if working is None:
                    mailto = task.get('mailto')
                    mailsent = task.get('mailsent')
                    if not mailsent and mailto is not None and mailto != '':
                        self.__logger("MailToAction added")
                        self.__action_items.append(MailToAction(
                            task.get_task_id()
                            , 'Backup'
                            , task_status
                            , task.get('owner_guid')
                            , task.get('owner_type')
                            , task.get('owner_name')
                            , task.get_creation_date_formatted()
                            , task.get('fullname')
                            , task.get('migration_result_filename')
                            , mailto
                            , task.get('topobject_id')
                            , task.get('topobject_type')
                            , task.get('topobject_name')
                        ))
                    finished = task_status.get_finished()
                    if finished is not None and finished.get_status() != 'success':
                        self.__logger("UnsuspendTaskAction added")
                        self.__action_items.append(UnsuspendTaskAction(task.get('session_path')))
        
        # Get all existing session to clean suspend history entries with
        # non-existing session_id.
        task_manager = pmm_task.getPMMTaskManager()
        existing_sessions = [task.get('session_path') for task in task_manager.realGetTaskList(['Backup', 'Restore'])]
        locking_sessions = pmm_suspend_handler.SuspendHandler.get_session_list(self.__logger)
        self.__logger("%r %r" % (existing_sessions, locking_sessions))
        for unknown_session in set(locking_sessions) - set(existing_sessions):
            self.__logger("UnsuspendTaskAction added")
            self.__action_items.append(UnsuspendTaskAction(unknown_session))

        # Get backup task sessions to clean
        session_cleaner = SessionCleaner()
        for session in session_cleaner.get_task_sessions():
            self.__logger("CleanTaskSessionAction added")
            self.__action_items.append(CleanTaskSessionAction(session))

        # Get orphaned backup sessions to clean
        for session in session_cleaner.get_orphaned_sessions():
            self.__logger("CleanOrphanedSessionAction added")
            self.__action_items.append(CleanOrphanedSessionAction(session))
        
        # Get restore task sessions to clean
        restoreSessionCleaner = RestoreSessionCleaner()
        for session in restoreSessionCleaner.get_task_sessions():
            self.__logger("CleanTaskSessionAction added")
            self.__action_items.append(CleanTaskSessionAction(session))
        
        # Get orphaned restore sessions to clean
        for session in restoreSessionCleaner.get_orphaned_sessions():
            self.__logger("CleanOrphanedSessionAction added")
            self.__action_items.append(CleanOrphanedSessionAction(session))
        
        for log_dir in get_obsolete_logs(self.__logger):
            self.__logger("DeleteActionLogAction added")
            self.__action_items.append(DeleteActionLogAction(log_dir))

        last_logs_rotation_time = PmmcliLogRotateAction.get_last_rotation_time(self.__logger)
        self.__logger("PmmcliLogRotateAction added")
        self.__action_items.append(PmmcliLogRotateAction('pmmcli.log', last_logs_rotation_time))
        
        self.__logger("PmmcliLogRotateAction added")
        self.__action_items.append(PmmcliLogRotateAction('pmmcli_daemon.log', last_logs_rotation_time))
        
        self.__logger("PmmcliLogRotateAction added")
        self.__action_items.append(PmmcliLogRotateAction('migration.log', last_logs_rotation_time))
        
        self.__logger("PmmcliLogRotateAction added")
        self.__action_items.append(PmmcliLogRotateAction('migration_handler.log', last_logs_rotation_time))

    def get_action_items(self):
        return self.__action_items

    def reason(self):
        reason = False
        # Get restore tasks list
        task_manager = pmm_task.getPMMTaskManager()
        restore_task_list = task_manager.realGetTaskList(['Backup','Deploy'])
        for task in restore_task_list:
            task_status = task_manager.operatorGetTaskStatus(task)
            working = task_status.get_working()
            if working:
                reason = True
                break
        return reason

    def process(self, logger):
        for action_item in self.get_action_items():
            try:
                action_item.process(logger)
            except Exception, e:
                logger("exception during action processing\nStacktrace is: " + stacktrace.stacktrace())

    def process_daemontasks(self,logger):
        daemon_tasks = DaemonTasks()
        processed = True
        for action_item in daemon_tasks.get_daemontask_actions():
            try:
                processed = processed and action_item.process(logger)
            except Exception, e:
                logger("exception during action processing\nStacktrace is: " + stacktrace.stacktrace())
        return processed
