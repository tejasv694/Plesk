# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
import base64
import codecs
import os
import sys
import time
import shutil
import osutil
import dirutil
import signal
import logging
import cStringIO
import sqlite3
import cPickle
import urllib
from xml.dom import minidom
from encoder import EncoderFile
from threading import RLock, currentThread
from cPickle import UnpicklingError, HIGHEST_PROTOCOL

import plesk_config
import pmm_config
from pmm_utils import PmmUtils
import pmm_dump_formatter
import pmmcli_daemon_service
import pmm_dump_access_service
import pmm_repository_access_service
from pmm_api_xml_protocols import TaskStatus, Stopped, Finished, TaskLog, WorkingProgress, Working, Starting, \
    Stopping, TaskList, TaskStatusRestore, TaskStatusMixed
from pmm_api_xml_protocols import Task as TaskElement

import validator
from execution_result import ExecutionResult, ExecutionResultRestore, ExecutionResultMixed, MessageType, DescriptionType

_logger = logging.getLogger("pmmcli.task")

_supported_task_statuses = {'success' : 10, 'info' : 20, 'warnings' : 30, 'error' : 40}


class InvalidTaskOperation(Exception):
    """Exception to indicate an invalid operation on task"""


class DatabaseTaskDumpMarshaller:
    def __init__(self, connection):
        self.__connection = connection

    def dump(self, task):
        """
        :type task: BaseDictionaryTask
        """
        cursor = self.__connection.cursor()
        if task.get_task_id() is None:
            _logger.debug('Create type=%s' % (task.get_tasktype()))
            cursor.execute('INSERT INTO tasks VALUES (?,?,?,?,?,?)', [
                None
                , task.get_tasktype()
                , task.get('owner_guid') if task.has_key('owner_guid') else None
                , task.get('topobject_id') if task.has_key('topobject_id') else None
                , task.get('topobject_type') if task.has_key('topobject_type') else None
                , urllib.quote(cPickle.dumps(task, HIGHEST_PROTOCOL))
            ])
            task.set_task_id(cursor.lastrowid)
        else:
            _logger.debug('Update task id=%s, type=%s' % (str(task.get_task_id()), task.get_tasktype()))
            cursor.execute('UPDATE tasks SET type=?, ownerGuid=?, topObjectId=?, topObjectType=?, dump=? WHERE id=?', [
                task.get_tasktype()
                , task.get('owner_guid') if task.has_key('owner_guid') else None
                , task.get('topobject_id') if task.has_key('topobject_id') else None
                , task.get('topobject_type') if task.has_key('topobject_type') else None
                , urllib.quote(cPickle.dumps(task, HIGHEST_PROTOCOL))
                , task.get_task_id()
            ])


class DatabaseTaskLoadMarshaller:
    def __init__(self, connection, task_id):
        self.__connection = connection
        self.__task_id = task_id

    def load(self):
        cursor = self.__connection.cursor()
        cursor.execute('SELECT dump FROM tasks WHERE id=?', [self.__task_id])
        row = cursor.fetchone()
        if None == row:
            raise InvalidTaskOperation('Task #%s does not exist' % str(self.__task_id))
        task = cPickle.loads(urllib.unquote(row[0]))
        task.set_task_id(self.__task_id)
        _logger.debug("Load task id=%s type=%s" % (str(task.get_task_id()), task.get_tasktype()))
        return task


class TaskHandler:
    def __init__(self, task_type):
        self.__task_type = task_type

    def get_task_type(self):
        return self.__task_type

    def stop_task(self, task):
        if not isinstance(task, Task):
            raise InvalidTaskOperation('TaskHandler does not bind with Task instance (%s)' % task.__class__.__name__)
        if task.get_stopped() or not task.is_active():
            return
        try:
            _logger.info("Stop the task #%s of the type %s (pid=%s)"
                         % (task.get_task_id(), task.get_tasktype(), task.get_os_pid()))
            osutil.kill(task.get_os_pid(), signal.SIGTERM)
            _logger.info("The task #%s is stopped" % task.get_task_id())
        except OSError, e:
            _logger.warning("Failed to stop the task #%s: %s" % (task.get_task_id(), str(e)))

    def remove_task(self, task):
        if not isinstance(task, Task):
            raise InvalidTaskOperation, 'TaskHandler does not bind with Task instance (%s)' % task.__class__.__name__
        # remove task-specific additional data here
        # this method is invoked from the task manager, that implements add/remove task operations
        # There is no additional data for tasks types, being processed with TaskHandler class

    def get_status(self, task):
        if not isinstance(task, Task):
            raise InvalidTaskOperation, 'TaskHandler does not bind with Task instance (%s)' % task.__class__.__name__
        if task.get_stopped():
            return StoppedTaskStatusCreator(task).response()
        else:
            active = task.is_active()
            if active:
                return RunningTaskStatusCreator(task).response()
            else:
                return FinishedTaskStatusCreator(task).response()

    def get_log(self, task):
        if not isinstance(task, Task):
            raise InvalidTaskOperation, 'TaskHandler does not bind with Task instance (%s)' % task.__class__.__name__
        # Have to be overwritten in inherited classes
        return ''


class BaseDictionaryTaskHandler(TaskHandler):
    def __init__(self, name='None'):
        TaskHandler.__init__(self, name)

    def get_log(self, task):
        if not isinstance(task, BaseDictionaryTask):
            raise InvalidTaskOperation('BaseDictionaryTaskHandler does not bind with BaseDictionaryTask instance (%s)'
                                       % task.__class__.__name__)
        try:
            return BaseDictionaryTaskLogCreator(task).response()
        except InvalidTaskOperation, x:
            _logger.warning("Exception raised in BaseDictionaryTaskHandler: %s" % str(x))
            return TaskHandler.get_log(self,task)


class BackupTaskHandler(BaseDictionaryTaskHandler):
    def __init__(self):
        BaseDictionaryTaskHandler.__init__(self, 'Backup')

    def stop_task(self, task):
        if not isinstance(task, BackupTask):
            raise InvalidTaskOperation('BackupTaskHandler does not bind with BackupTask instance (%s)'
                                       % task.__class__.__name__)
        if task.get_stopped() or not task.is_active():
            return
        session_path = task.get('session_path')
        if session_path:
            getPMMTaskManager().set_task_stopped(session_path)
        else:
            BaseDictionaryTaskHandler.stop_task(self, task)

    def remove_task(self, task):
        if not isinstance(task, BackupTask):
            raise InvalidTaskOperation('BackupTaskHandler does not bind with BackupTask instance (%s)'
                                       % task.__class__.__name__)
        task_dir = task.get('session_path')
        if os.path.isdir(task_dir):
            osutil.unlink_recursively(task_dir)

    def get_status(self, task):
        if not isinstance(task, BackupTask):
            raise InvalidTaskOperation('BackupTaskHandler does not bind with BackupTask instance (%s)'
                                       % task.__class__.__name__)
        active = task.is_active()
        if task.get_stopped():
            if active:
                return StoppingTaskStatusCreator(task).response()
            else:
                return StoppedTaskStatusCreator(task).response()
        else:
            if active:
                return RunningBackupTaskStatusCreator(task).response()
            else:
                return FinishedBackupTaskStatusCreator(task).response()


class ImportTaskHandler(BaseDictionaryTaskHandler):
    def __init__(self):
        BaseDictionaryTaskHandler.__init__(self, 'Import')

    def remove_task(self, task):
        if not isinstance(task, ImportTask):
            raise InvalidTaskOperation, 'ImportTaskHandler does not bind with DeployTask instance (%s)' % task.__class__.__name__

    def get_status(self, task):
        if not isinstance(task, ImportTask):
            raise InvalidTaskOperation, 'ImportTaskHandler does not bind with DeployTask instance (%s)' % task.__class__.__name__
        if task.get_stopped():
            return StoppedTaskStatusCreator(task).response()
        else:
            return SimpleTaskStatusCreator(task).response()

    def finish_task(self, task, err_code, err_msg):
        execution_result = ExecutionResult.factory()
        if err_code == 0:
            execution_result.set_status('success')
        else:
            execution_result.set_status('error')
            execution_result.add_message(MessageType(
                code=err_code,
                severity='error',
                description=DescriptionType(encoding='base64', valueOf_=base64.encodestring(err_msg))
            ))

        with open(task.get('migration_result_filename'), 'w+') as f:
            f.write(PmmUtils.convertToXmlString(execution_result, 'execution-result'))


class DeployTaskHandler(BaseDictionaryTaskHandler):
    def __init__(self):
        BaseDictionaryTaskHandler.__init__(self, 'Deploy')

    def stop_task(self, task):
        if not isinstance(task, DeployTask):
            raise InvalidTaskOperation('DeployTaskHandler does not bind with DeployTask instance (%s)'
                                       % task.__class__.__name__)
        if task.get_stopped() or not task.is_active():
            return
        session_path = task.get('session_path')
        if session_path:
            getPMMTaskManager().set_task_stopped(session_path)
        else:
            BaseDictionaryTaskHandler.stop_task(self, task)

    def remove_task(self, task):
        if not isinstance(task, DeployTask):
            raise InvalidTaskOperation('DeployTaskHandler does not bind with DeployTask instance (%s)'
                                       % task.__class__.__name__)

    def get_status(self, task):
        if not isinstance(task, DeployTask):
            raise InvalidTaskOperation('DeployTaskHandler does not bind with DeployTask instance (%s)'
                                       % task.__class__.__name__)
        active = task.is_active()
        if task.get_stopped():
            if active:
                return StoppingTaskStatusCreator(task).response()
            else:
                return StoppedTaskStatusCreator(task).response()
        else:
            if active:
                return RunningDeployTaskStatusCreator(task).response()
            else:
                return FinishedDeployTaskStatusCreator(task).response()


class ConflictResolveTaskHandler(BaseDictionaryTaskHandler):
    def __init__(self):
        BaseDictionaryTaskHandler.__init__(self,'ConflictResolve')

    def remove_task(self, task):
        if not isinstance(task, ConflictResolveTask):
            raise InvalidTaskOperation, 'ConflictResolveTaskHandler does not bind with ConflictResolveTask instance (%s)' % task.__class__.__name__

    def get_status(self, task):
        if not isinstance(task, ConflictResolveTask):
            raise InvalidTaskOperation, 'ConflictResolveTaskHandler does not bind with ConflictResolveTask instance (%s)' % task.__class__.__name__
        if task.get_stopped():
            return StoppedTaskStatusCreator(task).response()
        else:
            return SimpleTaskStatusCreator(task).response()


class RestoreTaskHandler(BaseDictionaryTaskHandler):
    def __init__(self):
        BaseDictionaryTaskHandler.__init__(self, 'Restore')

    def remove_task(self, task):
        if not isinstance(task, RestoreTask):
            raise InvalidTaskOperation('RestoreTaskHandler does not bind with RestoreTask instance (%s)' % task.__class__.__name__)
        nested_tasks = (
            task.try_get('import_task_id'),
            task.try_get('deploy_task_id'),
            task.try_get('conflict_resolve_task_id')
        )
        for task_id in nested_tasks:
            if task_id is not None:
                getPMMTaskManager().removeTask(task_id)

    def __get_execution_result(self, log_filename):
        if not log_filename:
            return

        execution_result = ExecutionResult.factory(log_location=None)
        fatal_message = None

        log_content = None
        try:
            log_file = open(log_filename, "rt")
            log_content = log_file.read()
            log_file.close()
        except:
            fatal_message = MessageType.factory(id=None, resolution=None)
            fatal_message.set_code('UtilityError')
            fatal_message.set_severity('error')
            fatal_message.set_description(DescriptionType(valueOf_="Execution result file (%s) can not be opened" % log_filename))

        if log_content is not None:
            try:
                execution_result.build(minidom.parseString(log_content).childNodes[0])
            except:
                fatal_message = MessageType.factory(id=None, resolution=None)
                fatal_message.set_code('UtilityError')
                fatal_message.set_severity('error')
                fatal_message.set_description(DescriptionType(valueOf_="Execution result file (%s) is not valid" % log_filename))

        if fatal_message is not None:
            execution_result.set_status('error')
            execution_result.add_message(fatal_message)
        return execution_result

    def __max_status(self, array):
        status = _supported_task_statuses['success']
        for item in array:
            status = max(status, _supported_task_statuses[item])
        for name, value in _supported_task_statuses.items():
            if status == value:
                return name
        return 'success'

    def get_status(self, task):
        if not isinstance(task, RestoreTask):
            raise InvalidTaskOperation('RestoreTaskHandler does not bind with RestoreTask instance (%s)'
                                       % task.__class__.__name__)
        finished = False

        conflict_resolve_task_status = None
        if task.try_get('conflict_resolve_task_id') is not None:
            nested_task = getPMMTaskManager().realGetTask(task.try_get('conflict_resolve_task_id'))
            if nested_task is not None:
                conflict_resolve_task_status = nested_task.get_status()
                if conflict_resolve_task_status is None:
                    conflict_resolve_task_status = NonExistentFinishedTaskStatusCreator(nested_task.get_task_id(), 'success', nested_task.get('migration_result_filename')).response()
                if conflict_resolve_task_status.get_finished() is not None:
                    _logger.debug('conflict_resolve_task_status finished %s' % conflict_resolve_task_status.get_finished().get_status())
                    finished = conflict_resolve_task_status.get_finished().get_status() == 'error'

        import_task_status = None
        if task.try_get('import_task_id') is not None:
            finished = False
            nested_task = getPMMTaskManager().realGetTask(task.try_get('import_task_id'))
            if nested_task is not None:
                import_task_status = nested_task.get_status()
                if import_task_status is None:
                    import_task_status = NonExistentFinishedTaskStatusCreator(nested_task.get_task_id(), 'success', nested_task.get('migration_result_filename')).response()
                if import_task_status.get_finished() is not None:
                    _logger.debug('import_task_status finished %s' % import_task_status.get_finished().get_status())
                    finished = import_task_status.get_finished().get_status() == 'error'

        deploy_task_status = None
        if task.try_get('deploy_task_id') is not None:
            finished = False
            nested_task = getPMMTaskManager().realGetTask(task.try_get('deploy_task_id'))
            if nested_task is not None:
                deploy_task_status = nested_task.get_status()
                if deploy_task_status is None:
                    message = MessageType(
                        severity='warning',
                        description=DescriptionType(valueOf_='Unable to find the deploy task #%s. Check the restoration result manually.' % str(nested_task.get_task_id()))
                    )
                    deploy_task_status = NonExistentFinishedTaskStatusCreator(nested_task.get_task_id(), 'warnings', nested_task.get('migration_result_filename'), message).response()
                finished = deploy_task_status.get_finished() is not None

        if finished and deploy_task_status:
            if conflict_resolve_task_status and conflict_resolve_task_status.get_finished() is None:
                log_file_path = os.path.join(task.get('session_path'), ConflictResolveTask.get_result_file_name())
                conflict_resolve_task_status = NonExistentFinishedTaskStatusCreator(task.get('conflict_resolve_task_id'), 'success', log_file_path).response()
            if import_task_status and import_task_status.get_finished() is None:
                log_file_path = os.path.join(task.get('session_path'), ImportTask.get_result_file_name())
                import_task_status = NonExistentFinishedTaskStatusCreator(task.get('import_task_id'), 'success', log_file_path).response()

        restore_status = TaskStatusRestore.factory(log_location=None)
        restore_status.set_conflict_resolve(conflict_resolve_task_status)
        restore_status.set_import(import_task_status)
        restore_status.set_deploy(deploy_task_status)
        if finished is True:
            if task.get('finished') is not True:
                task.set('finished', True)
                task_statuses = []
                conflict_resolve_execution_result = None
                if conflict_resolve_task_status:
                    conflict_resolve_finished = conflict_resolve_task_status.get_finished()
                    if conflict_resolve_finished is not None:
                        conflict_resolve_execution_result = self.__get_execution_result(conflict_resolve_finished.get_log_location())
                        task_statuses.append(conflict_resolve_finished.get_status())

                import_execution_result = None
                if import_task_status:
                    import_finished = import_task_status.get_finished()
                    if import_finished is not None:
                        import_execution_result = self.__get_execution_result(import_finished.get_log_location())
                        task_statuses.append(import_finished.get_status())

                deploy_execution_result = None
                if deploy_task_status is not None:
                    deploy_execution_result = self.__get_execution_result(deploy_task_status.get_finished().get_log_location())
                    task_statuses.append(deploy_task_status.get_finished().get_status())

                task_status = self.__max_status(task_statuses)
                _logger.debug('task_statuses: %s' % str(task_statuses))

                execution_result_restore = ExecutionResultRestore.factory(log_location=None,
                                                                          conflict_resolve=conflict_resolve_execution_result,
                                                                          importxx=import_execution_result,
                                                                          deploy=deploy_execution_result)
                execution_result_restore.set_status(task_status)
                execution_result_restore_filename = os.path.join(task.get('session_path'), 'restore-result.xml')
                task.set('status', task_status)
                if not os.path.isfile(execution_result_restore_filename):
                    if not os.path.exists(task.get('session_path')):
                        dirutil.mkdirs(task.get('session_path'), 0750, -1, plesk_config.psaadm_gid())
                    execution_result_restore_file = open(execution_result_restore_filename, 'wt')
                    try:
                        resp_str = cStringIO.StringIO()
                        resp_encoded = EncoderFile(resp_str, "utf-8")
                        resp_encoded.write('<?xml version="1.0" encoding="UTF-8"?>\n')
                        execution_result_restore.export(resp_encoded, 0, name_='restore')
                        execution_result_restore_file.write(resp_str.getvalue())
                    finally:
                        execution_result_restore_file.close()
                        osutil.chown(execution_result_restore_filename, 'psaadm', 'psaadm')
            restore_status.set_status(task.get('status'))
            restore_status.set_log_location(task.get('migration_result_filename'))
        status = TaskStatus.factory()
        mixed = TaskStatusMixed.factory(log_location=None)
        mixed.set_restore(restore_status)
        if finished is True:
            mixed.set_status(restore_status.get_status())
            mixed.set_log_location(restore_status.get_log_location())
        status.set_mixed(mixed)
        return status


class Task:
    def __init__(self, cmd, pid, task_handler = TaskHandler("None")):
        self.__task_handler = task_handler
        self.__tasktype = str(task_handler.get_task_type())
        self.__cmd = cmd
        self.__os_pid = int(pid)
        self.__task_id = None
        self.__creation_date = time.time()
        self.__stopped = False
        self.__removed = False
        self.__version = Task.Version()
        getPMMTaskManager().addTask(self)

    def Version():
        return '10.2'

    Version = staticmethod(Version)

    def get_task_handler(self):
        return self.__task_handler

    def dump(self, dump_marshaller):
        if self.__removed:
            raise InvalidTaskOperation, 'Could not dump removed task'
        pdump = dump_marshaller.dump
        pdump(self)

    def load(load_marshaller):
        pload = load_marshaller.load
        self = pload()
        self.on_load()
        return self

    load = staticmethod(load)

    def is_active(self):
        active = osutil.is_active(self.__os_pid)
        same_cmd = False
        if active:
            process_cmd = osutil.get_cmd(self.__os_pid)
            # This deal with 'get_cmd' implementation differences on Windows and Linux platforms
            if len(process_cmd) > 0:
                # Because of using 'nice' command, self.__cmd can be equal e.g. "nice --adjustment=20 ".process_cmd
                same_cmd = process_cmd in self.__cmd;
            if not same_cmd:
                _logger.debug("process (%s) found with the same pid as %s task pid (%d)" % (process_cmd, self.__tasktype, self.__os_pid))
        return same_cmd

    def stop(self):
        self.__task_handler.stop_task(self)
        self.__stopped = True

    def remove(self):
        _logger.debug("Remove task %s" % self.__task_id)
        self.__task_handler.remove_task(self)
        self.__removed = True

    def get_status(self):
        status = self.__task_handler.get_status(self)
        if not isinstance(status, TaskStatus):
            raise InvalidTaskOperation, "TaskStatus returned (%s) is not TaskStatus instance" % status.__class__.__name__
        status.set_task_id(self.__task_id)
        return status

    def get_log(self):
        return self.__task_handler.get_log(self)

    def get_os_pid(self):
        return self.__os_pid

    def set_os_pid(self, pid):
        self.__os_pid = pid

    def get_task_id(self):
        return self.__task_id

    def get_tasktype(self):
        return self.__tasktype

    def get_creation_date_formatted(self):
        return time.strftime('%Y-%b-%d %H:%M:%S',time.localtime(self.__creation_date))

    def get_creation_date(self):
        return self.__creation_date

    def get_stopped(self):
        return self.__stopped

    def set_task_id(self, task_id):
        self.__task_id = task_id

    def on_load(self):
        if self.__version != Task.Version():
            raise InvalidTaskOperation, "Task loaded version '%s' differs from current version '%s'" % ( self.__version, Task.Version() )

    def get_child_task_ids(self):
        return []

class BaseDictionaryTask(Task):
    def __init__(self, cmd, pid, dictionary = {}, task_handler = BaseDictionaryTaskHandler("None")):
        self.__dict = dictionary
        Task.__init__(self, cmd, pid, task_handler)

    def try_get(self, key_name, default=None):
        if key_name in self.__dict.keys():
            return self.__dict[key_name]
        else:
            return default

    def get(self, key_name):
        return self.__dict[key_name]

    def set(self, key_name, key_value, updateAfterSet=False):
        self.__dict[key_name] = key_value
        if updateAfterSet:
            getPMMTaskManager().updateTask(self)

    def keys(self):
        return self.__dict.keys()

    def has_key(self, key_name):
        return self.__dict.has_key(key_name)


class DictionaryTask(BaseDictionaryTask):
    def __init__(self, cmd, pid, dictionary = {}, task_handler = TaskHandler("None")):
        for key in self.required_keys():
            if not dictionary.has_key(key):
                dictionary[key] = None
        BaseDictionaryTask.__init__(self, cmd, pid, dictionary, task_handler)

    def on_load(self):
        BaseDictionaryTask.on_load(self)
        for key in self.required_keys():
            if not self.has_key(key):
                self.set(key, None)

    def required_keys():
        return []

    required_keys = staticmethod(required_keys)


class BackupTask(DictionaryTask):
    def __init__(self, cmd, pid, dictionary = {}, task_handler = BackupTaskHandler()):
        DictionaryTask.__init__(self, cmd, pid, dictionary, task_handler)
        self.set('log_filename', os.path.join(self.get('session_path'),'psadump.log'))
        self.set('migration_result_filename', os.path.join(self.get('session_path'),'migration.result'))
        getPMMTaskManager().updateTask(self)

    def required_keys():
        return ["owner_guid",
                "owner_type",
                "owner_name",
                "dumps_storage_credentials",
                "fullname",
                "backup_profile_name",
                "mailto",
                "topobject_id",
                "topobject_type",
                "topobject_name",
                "mailsent",
                "host_credentials",
                "log_filename",
                "migration_result_filename",
                "session_path"]

    required_keys = staticmethod(required_keys)


class ImportTask(DictionaryTask):
    def __init__(self, cmd, pid, dictionary={}, task_handler=ImportTaskHandler()):
        DictionaryTask.__init__(self, cmd, pid, dictionary, task_handler)
        self.set('log_filename', os.path.join(self.get('session_path'), 'migration.log'))
        self.set('migration_result_filename', os.path.join(self.get('session_path'), ImportTask.get_result_file_name()))
        getPMMTaskManager().updateTask(self)

    def finish(self, err_code, err_msg):
        self.get_task_handler().finish_task(self, err_code, err_msg)

    @staticmethod
    def required_keys():
        return ["log_filename",
                "migration_result_filename",
                "session_path"]

    @staticmethod
    def get_result_file_name():
        return 'import.result'


class DeployTask(DictionaryTask):
    def __init__(self, cmd, pid, dictionary = {}, task_handler = DeployTaskHandler()):
        DictionaryTask.__init__(self, cmd, pid, dictionary, task_handler)
        self.set('log_filename', os.path.join(self.get('session_path'),'migration.log'))
        self.set('migration_result_filename', os.path.join(self.get('session_path'),'deploy.result'))
        getPMMTaskManager().updateTask(self)

    def required_keys():
        return ["owner_guid",
                "owner_type",
                "owner_name",
                "dumps_storage_credentials",
                "fullname",
                "backup_profile_name",
                "mailto",
                "topobject_id",
                "topobject_type",
                "topobject_name",
                "mailsent",
                "host_credentials",
                "log_filename",
                "migration_result_filename",
                "session_path",
                "delete_dump"]

    required_keys = staticmethod(required_keys)


class ConflictResolveTask(DictionaryTask):
    def __init__(self, dictionary = {}, task_handler = ConflictResolveTaskHandler()):
        DictionaryTask.__init__(self, '', 1073741824, dictionary, task_handler)
        self.set('migration_result_filename', os.path.join(self.get('session_path'),'conflict-resolve.log.xml'))
        self.set('log_filename', os.path.join(self.get('session_path'),'conflict-resolve.log'))
        getPMMTaskManager().updateTask(self)

    def get(self, key_name):
        if key_name == 'migration_result_filename':
            return os.path.join(self.get('session_path'), ConflictResolveTask.get_result_file_name())
        if key_name == 'log_filename':
            return os.path.join(self.get('session_path'), 'conflict-resolve.log')
        return DictionaryTask.get(self, key_name)

    @staticmethod
    def get_result_file_name():
        return 'conflict-resolve.log.xml'

    @staticmethod
    def required_keys():
        return ["log_filename",
                "migration_result_filename",
                "session_path"]


class RestoreTask(DictionaryTask):
    def __init__(self, cmd, pid, dictionary = {}, task_handler = RestoreTaskHandler()):
        DictionaryTask.__init__(self, cmd, pid, dictionary, task_handler)
        self.set('finished', False)
        self.set('log_filename', '')
        self.set('migration_result_filename', 'restore-result.xml')
        getPMMTaskManager().updateTask(self)

    def get(self, key_name):
        if key_name == 'migration_result_filename':
            return os.path.join(self.get('session_path'),'restore-result.xml')
        return DictionaryTask.get(self, key_name)

    def required_keys():
        return ["session_path",
                "finished",
                "log_filename",
                "migration_result_filename",
                "conflict_resolve_task_id",
                "import_task_id",
                "deploy_task_id",
                "owner_guid",
                "owner_type",
                "owner_name",
                "topobject_id",
                "topobject_type",
                "topobject_name",
                "topobject_guid",
                "dumps_storage_credentials",
                "fullname",
                "name"]

    def get_child_task_ids(self):
        child_task_ids = []
        if self.get('conflict_resolve_task_id'):
            child_task_ids.append(self.get('conflict_resolve_task_id'))
        if self.get('import_task_id'):
            child_task_ids.append(self.get('import_task_id'))
        if self.get('deploy_task_id'):
            child_task_ids.append(self.get('deploy_task_id'))
        return child_task_ids

    required_keys = staticmethod(required_keys)


execution_result_validator = None


def getExecutionResultValidator():
    global execution_result_validator
    if not execution_result_validator:
        execution_result_validator = XsdValidator(pmm_config.execution_result_schema())
    return execution_result_validator


class XsdValidator:
    def __init__(self, schema):
        self.__validator = validator.Validator(schema)

    def do(self, xml):
        return self.__validator.do(xml)


class PMMTaskPersistentStorage(object):
    def __init__(self):
        self.accessMutex = RLock()
        database = os.path.join(pmm_config.tasks_dir(), 'tasks.db')
        if not os.path.exists(database):
            self.__make_persistent_storage(database)
        self.__connection = sqlite3.connect(database, timeout=30, isolation_level=None)
        self.__connection.text_factory = str

    def __del__(self):
        self.__connection.close()

    def __make_persistent_storage(self, database):
        tasksDirectory = os.path.dirname(database)
        if not os.path.exists(tasksDirectory):
            dirutil.mkdirs(tasksDirectory, 0750)
        try:
            with sqlite3.connect(database) as connection:
                connection.execute('CREATE TABLE tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT, ownerGuid TEXT, topObjectId INTEGER, topObjectType TEXT, dump TEXT)')
                connection.execute('CREATE INDEX tasks_type ON tasks (type)')
                connection.execute('CREATE INDEX tasks_ownerGuid ON tasks (ownerGuid)')
                connection.execute('CREATE INDEX tasks_topObjectId ON tasks (topObjectId)')
                connection.execute('CREATE INDEX tasks_topObjectType ON tasks (topObjectType)')
        except:
            osutil.unlink_nothrow(database)
            raise

    def __loadTask(self, task_id):
        loader = DatabaseTaskLoadMarshaller(self.__connection, task_id)
        try:
            return Task.load(loader)
        except (AttributeError, ValueError, EOFError, ImportError, IndexError, UnpicklingError, KeyError, InvalidTaskOperation), e:
            _logger.error("Could not load task #%s: Exception raised: %s" % (str(task_id), str(e)))
            _logger.info("The task #%s will be removed from the task storage" % str(task_id))
            try:
                self.__connection.execute('DELETE FROM tasks WHERE id=?', [task_id])
            except Exception as e:
                _logger.debug('Unable to remove task #%s: %s' % (str(task_id), str(e)))
            return None


    def getTask(self,task_id):
        return self.__loadTask(task_id)

    def getTasks(self, types, owner_guids, topobject_id, topobject_type):
        sql = 'SELECT id FROM tasks WHERE 1=1'
        parameters = []
        if types:
            sql += ' AND type IN (%s)' % ','.join(['?'] * len(types))
            parameters += types
        if owner_guids:
            sql += ' AND ownerGuid IN (%s)' % ','.join(['?'] * len(owner_guids))
            parameters += owner_guids
        if topobject_id:
            sql += ' AND topObjectId = ?'
            parameters.append(topobject_id)
        if topobject_type:
            sql += ' AND topObjectType = ?'
            parameters.append(topobject_type)

        task_list = []
        for row in self.__connection.execute(sql, parameters):
            task_item = self.__loadTask(row[0])
            if (task_item is None) or (not isinstance(task_item, BaseDictionaryTask)):
                continue
            task_list.append(task_item)

        # sort task list by creation_date
        temp_array = [(-item.get_creation_date(), index, item) for index, item in enumerate(task_list)]
        temp_array.sort()
        return [item[-1] for item in temp_array]

    def saveTask(self, task):
        dumper = DatabaseTaskDumpMarshaller(self.__connection)
        task.dump(dumper)

    def removeTask(self, task):
        self.__connection.execute('DELETE FROM tasks WHERE id=?', [task.get_task_id()])


class FinishedTaskStatusCreator:
    def __init__(self, task):
        self.__task = task

    def response(self):
        task_status = Finished( status = 'success')
        return TaskStatus ( finished = task_status )


class FinishedBackupTaskStatusCreator(FinishedTaskStatusCreator):
    def __init__(self, task):
        if not isinstance(task, BackupTask):
            raise InvalidTaskOperation, 'FinishedBackupTaskStatusCreator does not bind with BackupTask instance' % task.__class__.__name__
        self.__task = task  # type: BackupTask
        fullname = self.__task.get('fullname')
        if not fullname:
            dump_file_name = getPMMTaskManager().get_dump_file_name(os.path.join(self.__task.get('session_path'), 'dump-name'))
            if dump_file_name:
                # dump file name here could be
                formatter = pmm_dump_formatter.DumpsStorageCredentialsFormatter(self.__task.get('dumps_storage_credentials'))
                file_name = formatter.getPathRelativeToDumpStorage(dump_file_name)
                self.__task.set('fullname', file_name)
                self.__task.set('name', os.path.basename(file_name))
                getPMMTaskManager().updateTask(self.__task)
        err_file_name = os.path.join(self.__task.get('session_path'),'.stderr')
        err_string = None
        if os.path.isfile(err_file_name):
            err = open(err_file_name,"r")
            err_string = err.read()
            err.close()
            shutil.copy2(err_file_name,os.path.join(self.__task.get('session_path'),'stderr'))
            osutil.unlink_nothrow(err_file_name)

        if err_string and err_string != '':
            stderr_string = "\n== STDERR ====================\n" + err_string + "\n==============================\n"
            log_file_name = self.__task.get('log_filename')
            if os.path.isfile(log_file_name):
                log_file = open(log_file_name,"a")
                log_file.write(stderr_string)
                log_file.close()

    def response(self):
        local_dump_created = False
        export_dump_created = False
        pid = str(self.__task.get_task_id())
        status_file_path = os.path.join(self.__task.get('session_path'),'migration.result')
        _logger.debug("Get task (%s) status from migration.result..." % pid)
        _logger.debug("Session path (%s)" % self.__task.get('session_path'))
        if os.path.isfile(status_file_path):
            #load migration result from file
            try:
                warns_getter = pmm_dump_access_service.XmlElementSelector(['message', 'object'])
                status_getter = pmm_dump_access_service.XmlElementAttrGetter('execution-result', 'status')
                local_dump_status_getter = pmm_dump_access_service.XmlElementAttrGetter('execution-result',
                                                                                        'local-dump-created')
                export_dump_status_getter = pmm_dump_access_service.XmlElementAttrGetter('execution-result',
                                                                                         'export-dump-created')
                xml_handler = pmm_dump_access_service.XmlHandler([warns_getter, status_getter, local_dump_status_getter,
                                                                  export_dump_status_getter])
                xml_processor = pmm_dump_access_service.InfoXMLProcessor(status_file_path, xml_handler)
                xml_processor.process()
                status_attribute = status_getter.get_attribute_value()
                local_dump_created = 'true' == local_dump_status_getter.get_attribute_value()
                export_dump_created = 'true' == export_dump_status_getter.get_attribute_value()
                if not _supported_task_statuses.has_key(status_attribute):
                    _logger.debug("Status '%s' not in supported statuses list. Status will be caculated by XML structure", status_attribute)
                    # task working process (backup or deployer) writes only 'error' or 'success' as status of finished task
                    # if status task is not 'error' we should suppose it is 'success'
                    if status_attribute != 'error':
                        status_attribute = 'success'
                    # if execution result has any context then reduce status to 'warning'
                    if status_attribute == 'success' and warns_getter.get_element_found():
                        status_attribute = 'warnings'
                task_status = Finished( status = status_attribute, log_location = self.__task.get('migration_result_filename'))
            except:
                _logger.debug("Execution result file %s exists but has wrong format. The task %s will be marked as finished with success." % (status_file_path, pid))
                task_status = Finished( status = 'success', log_location = self.__task.get('migration_result_filename'))
        else:
            _logger.debug("Execution result file %s isn't found. The task %s will be marked as finished with error." % (status_file_path, pid))
            task_status = Finished( status = 'error', log_location = self.__task.get('migration_result_filename'))

        if task_status.status == 'error' and \
                ('temp_files_removed' not in self.__task.keys() or self.__task.get('temp_files_removed') != 1):
            self.__task.set('temp_files_removed', 1)
            getPMMTaskManager().updateTask(self.__task)
            local_dump_name_file = os.path.join(self.__task.get('session_path'), 'local-dump-name')
            formatter = pmm_dump_formatter.DumpsStorageCredentialsFormatter(
                self.__task.get('dumps_storage_credentials'))
            local_dump_name = None
            if os.path.isfile(local_dump_name_file):
                with open(local_dump_name_file) as f:
                    local_dump_name = f.read()

            if formatter.get_storage_type() in ['foreign-ftp', 'extension']:
                if local_dump_name is not None and not local_dump_created:
                    _logger.debug("We are going to delete incompleted backup from local storage %s" % local_dump_name)
                    pmm_repository_access_service.LocalRepositoryAccessService(
                        self.__task.get('dumps_storage_credentials')).deleteIncompleteDumps(local_dump_name,
                                                                                            self.__task.get('session_path'),
                                                                                            plesk_config.get('DUMP_D'))
                export_dump_name_file = os.path.join(self.__task.get('session_path'), 'export-dump-name')
                if os.path.isfile(export_dump_name_file) and not export_dump_created:
                    with open(export_dump_name_file) as f:
                        export_dump_name = f.read()
                    _logger.debug("We are going to delete incompleted backup from remote storage %s" % export_dump_name)
                    pmm_repository_access_service.FtpRepositoryAccessService(
                        self.__task.get('dumps_storage_credentials')).deleteIncompleteDumps(export_dump_name,
                                                                                            self.__task.get('session_path'))
            elif local_dump_name is not None and not local_dump_created:
                _logger.debug("We are going to delete incompleted backup from local storage %s" % local_dump_name)
                pmm_repository_access_service.LocalRepositoryAccessService(
                    self.__task.get('dumps_storage_credentials')).deleteIncompleteDumps(local_dump_name,
                                                                                        self.__task.get('session_path'))

        return TaskStatus(finished=task_status)


class FinishedDeployTaskStatusCreator(FinishedTaskStatusCreator):
    def __init__(self, task):
        if not isinstance(task, DeployTask):
            raise InvalidTaskOperation, 'FinishedDeployTaskStatusCreator does not bind with DeployTask instance' % task.__class__.__name__
        self.__task = task
        fullname = self.__task.get('fullname')
        if not fullname:
            dump_file_name = getPMMTaskManager().get_dump_file_name(os.path.join(self.__task.get('session_path'), 'dump-name'))
            if dump_file_name:
                # dump file name here could be
                formatter = pmm_dump_formatter.DumpsStorageCredentialsFormatter(self.__task.get('dumps_storage_credentials'))
                storage = formatter.getDumpStorageNoLogin()
                if formatter.get_storage_type() == 'foreign-ftp':
                    # pmm-ras could return mixed separators in the name of exported file for ftp storage type
                    dump_file_name = dump_file_name.replace('\\','/')
                file_name = dump_file_name.replace(storage,'')
                self.__task.set('fullname', file_name)
                self.__task.set('name', file_name)
                getPMMTaskManager().updateTask(self.__task)
        err_file_name = os.path.join(self.__task.get('session_path'),'.stderr')
        err_string = None
        if os.path.isfile(err_file_name):
            err = open(err_file_name,"r")
            err_string = err.read()
            err.close()
            shutil.copy2(err_file_name,os.path.join(self.__task.get('session_path'),'stderr'))
            osutil.unlink_nothrow(err_file_name)

        if err_string and err_string != '':
            stderr_string = "\n== STDERR ====================\n" + err_string + "\n==============================\n"
            log_file_name = self.__task.get('log_filename')
            if os.path.isfile(log_file_name):
                log_file = open(log_file_name,"a")
                log_file.write(stderr_string)
                log_file.close()

    def response(self):
        task_status = None
        pid = str(self.__task.get_task_id())
        status_file_path = os.path.join(self.__task.get('session_path'),'deploy.result')
        _logger.debug("Get task (%s) status from deploy.result..." % pid)
        _logger.debug("Session path (%s)" % self.__task.get('session_path'))
        if os.path.isfile(status_file_path):
            #load migration result from file
            try:
                status_getter = pmm_dump_access_service.XmlElementAttrGetter('execution-result','status')
                warns_getter = pmm_dump_access_service.XmlElementSelector(['message','object'])
                xml_processor = pmm_dump_access_service.InfoXMLProcessor( status_file_path, pmm_dump_access_service.XmlHandler([status_getter, warns_getter]))
                xml_processor.process()
                status_attribute = status_getter.get_attribute_value()
                if not _supported_task_statuses.has_key(status_attribute):
                    _logger.debug("Status '%s' not in supported statuses list. Status will be caculated by XML structure", status_attribute)
                    # task working process (backup or deployer) writes only 'error' or 'success' as status of finished task
                    # if status task is not 'error' we should suppose it is 'success'
                    if status_attribute != 'error':
                        status_attribute = 'success'
                    warns_found = warns_getter.get_element_found()
                    # if execution result has any context then reduce status to 'warning'
                    if status_attribute == 'success' and warns_found:
                        status_attribute = 'warnings'
                task_status = Finished( status = status_attribute, log_location = self.__task.get('migration_result_filename'))
            except:
                _logger.debug("Execution result file %s exists but has wrong format. The task %s will be marked as finished with success." % (status_file_path, pid))
                task_status = Finished( status = 'success', log_location = self.__task.get('migration_result_filename'))
        else:
            _logger.debug("Execution result file %s isn't found. The task %s will be marked as finished with error. \
                          Perhaps %s application missing, has incorrect permissions or unexpectedly terminated" % (status_file_path, pid, pmm_config.deployer()))
            task_status = Finished( status = 'error', log_location = self.__task.get('migration_result_filename'))
            execution_result = ExecutionResult.factory(log_location = self.__task.get('migration_result_filename'))
            execution_result.set_status('error')
            message = MessageType(
                severity='error',
                description=DescriptionType(valueOf_="Perhaps %s application missing, has incorrect permissions or unexpectedly terminated" % pmm_config.deployer())
            )
            execution_result.add_message(message)
            if not os.path.isdir(self.__task.get('session_path')):
                dirutil.mkdirs(self.__task.get('session_path'), 0750)
            execution_result_file = open(status_file_path, 'wt')
            try:
                execution_result.export(execution_result_file, 0, name_ = 'execution-result')
            finally:
                execution_result_file.close()
                osutil.chown(status_file_path, 'psaadm', 'psaadm')

        return TaskStatus ( finished = task_status )


class StoppingTaskStatusCreator:
    def __init__(self, task):
        self.__task = task

    def response(self):
        return TaskStatus(stopping=Stopping())


class StoppedTaskStatusCreator:
    def __init__(self, task):
        self.__task = task

    def response(self):
        return TaskStatus(stopped=Stopped())


class BaseDictionaryTaskLogCreator:
    def __init__(self, task):
        self.__task = task
        if not isinstance(task, BaseDictionaryTask):
            raise InvalidTaskOperation, "BaseDictionaryTaskLogCreator does not bind with BaseDictionaryTask instance %s" % task.__class__.__name__
        if not task.has_key('log_filename'):
            raise InvalidTaskOperation, "BaseDictionaryTaskLogCreator error: BaseDictionaryTask instance (%s) does not have 'log_filename' dictionary key" % task.__class__.__name__
        self.__task_log = task.get('log_filename')

    def response(self):
        log_string = "Log file \'" + self.__task_log + "\' does not exist"

        if os.path.isfile(self.__task_log):
            log = open(self.__task_log,"rt")
            log_string = log.read().decode('latin-1')
            log.close()

        return TaskLog ( log_string.encode('utf-8') )


class RunningTaskStatusCreator:
    def __init__(self, task):
        self.__task = task

    def response(self):
        task_status = Working( starting = Starting() )
        return TaskStatus ( working = task_status )


class RunningBackupTaskStatusCreator(RunningTaskStatusCreator):
    def __init__(self, task):
        if not isinstance(task, BackupTask):
            raise InvalidTaskOperation('RunningBackupTaskStatusCreator does not bind with BackupTask instance (%s)'
                                       % task.__class__.__name__)
        self.__task = task

    def response(self):
        task_status = None
        status_file_path = os.path.join(self.__task.get('session_path'), 'dump-status.xml')
        if os.path.isfile(status_file_path):
            # load dumping status from file
            status_file = open(status_file_path,"rt")
            status_file_content = status_file.read()
            status_file.close()
            dumping_status = WorkingProgress.factory()
            dumping_status.build(minidom.parseString(status_file_content).childNodes[0])
            task_status = Working(dumping=dumping_status)
        else:
            task_status = Working(starting=Starting())

        return TaskStatus(working=task_status)


class SimpleTaskStatusCreator:
    def __init__(self, task):
        self.__task = task

    def response(self):
        migration_result_path = os.path.join(self.__task.get('migration_result_filename'))
        if os.path.isfile(migration_result_path):
            #load deploy status from file
            migration_result_file = open(migration_result_path,"rt")
            migration_result_content = migration_result_file.read()
            migration_result_file.close()
            execution_result = ExecutionResult.factory()
            execution_result.build(minidom.parseString(migration_result_content).childNodes[0])
            task_status = Finished( status = execution_result.get_status(), log_location = self.__task.get('migration_result_filename') )
            return TaskStatus ( finished = task_status )
        else:
            task_status = Working( starting = Starting() )
            return TaskStatus ( working = task_status )


class RunningDeployTaskStatusCreator(RunningTaskStatusCreator):
    def __init__(self, task):
        if not isinstance(task, DeployTask):
            raise InvalidTaskOperation('RunningDeployTaskStatusCreator does not bind with DeployTask instance (%s)'
                                       % task.__class__.__name__)
        self.__task = task

    def response(self):
        task_status = None
        status_file_path = os.path.join(self.__task.get('session_path'), 'migration.status')
        if os.path.isfile(status_file_path):
            # load deploy status from file
            status_file = open(status_file_path,"rt")
            status_file_content = status_file.read()
            status_file.close()
            deploy_status = WorkingProgress.factory()
            deploy_status.build(minidom.parseString(status_file_content).childNodes[0])
            task_status = Working(deploy=deploy_status)
        else:
            task_status = Working(starting=Starting())

        return TaskStatus(working=task_status)


class NonExistentFinishedTaskStatusCreator:
    def __init__(self, taskId, status, logFilePath, message=None):
        self._taskId = taskId
        self._status = status
        self.logFilePath = logFilePath
        self._message = message

    def response(self):
        if not os.path.exists(self.logFilePath):
            executionResult = ExecutionResult(status=self._status)
            if self._message:
                executionResult.add_message(self._message)
            if not os.path.exists(os.path.dirname(self.logFilePath)):
                dirutil.mkdirs(os.path.dirname(self.logFilePath), 0750, -1, plesk_config.psaadm_gid())
            with codecs.open(self.logFilePath, 'w', encoding='utf-8') as executionResultFile:
                osutil.chown(self.logFilePath, 'psaadm', 'psaadm')
                executionResultFile.write('<?xml version="1.0" encoding="UTF-8"?>\n')
                executionResult.export(executionResultFile, 0, name_='execution-result')
        return TaskStatus(task_id=self._taskId, finished=Finished(status=self._status, log_location=self.logFilePath))


pmmTaskManager = None


def getPMMTaskManager():
    global pmmTaskManager
    if not pmmTaskManager:
        pmmTaskManager = PMMTaskManager()
    return pmmTaskManager


class PMMTaskManager(object):
    def __init__(self):
        self.persistent_storage = PMMTaskPersistentStorage()
        self.stateMutex = RLock()

    def realGetTaskList(self, types, owner_guids = frozenset(), topobject_id = None, topobject_type = None):
        task_list = self.persistent_storage.getTasks(types, owner_guids, topobject_id, topobject_type)
        return task_list

    def realGetTask(self, task_id):
        task = self.persistent_storage.getTask(task_id)
        if task is not None:
            self.operatorGetTaskStatus(task)
        return task

    def __operatorGetTaskLog(self, task):
        return task.get_log()

    def operatorGetTaskStatus(self, task):
        return task.get_status()

    def __operatorGetTask(self, task):
        task_list = TaskList.factory()
        task_object = self.__operatorGetTaskObject(task)

        task_list.add_task(task_object)
        return task_list

    def __operatorGetTaskObject(self, task):
        if task is None:
            return None
        dumps_storage_credentials_value = None
        owner_guid_value = None
        owner_type_value = None
        fullname_value = None
        name_value = None
        backup_profile_name_value = None
        mailto_value = None
        additional_info_value = None
        if isinstance(task, BaseDictionaryTask):
            if task.has_key('dumps_storage_credentials'):
                dumps_storage_credentials_value = task.get('dumps_storage_credentials')
            if task.has_key('owner_guid'):
                owner_guid_value = task.get('owner_guid')
            if task.has_key('owner_type'):
                owner_type_value = task.get('owner_type')
            if task.has_key('fullname'):
                fullname_value = task.get('fullname')
            if task.has_key('name'):
                name_value = task.get('name')
            if task.has_key('backup_profile_name'):
                backup_profile_name_value = task.get('backup_profile_name')
            if task.has_key('mailto'):
                mailto_value = task.get('mailto')
            if task.has_key('additional_info'):
                additional_info_value = task.get('additional_info')
        if dumps_storage_credentials_value:
            dumps_storage_credentials_formatter = pmm_dump_formatter.DumpsStorageCredentialsFormatter(
                dumps_storage_credentials_value)
            task_object = TaskElement.factory(task_id=task.get_task_id(), task_type=task.get_tasktype(),
                                              owner_guid=owner_guid_value, owner_type=owner_type_value,
                                              fullname=fullname_value, creation_date=task.get_creation_date_formatted(),
                                              name=name_value, backup_profile_name=backup_profile_name_value,
                                              mail_to=mailto_value, task_status=self.operatorGetTaskStatus(task),
                                              dumps_storage_credentials=dumps_storage_credentials_formatter.buildXml(),
                                              additional_info=additional_info_value)
        else:
            task_object = TaskElement.factory(task_id=task.get_task_id(), task_type=task.get_tasktype(),
                                              owner_guid=owner_guid_value, owner_type=owner_type_value,
                                              fullname=fullname_value, creation_date=task.get_creation_date_formatted(),
                                              name=name_value, backup_profile_name=backup_profile_name_value,
                                              mail_to=mailto_value, task_status=self.operatorGetTaskStatus(task),
                                              additional_info=additional_info_value)
        return task_object

    def __operatorStopTask(self, task):
        if not task.get_stopped():
            task.stop()
            self.persistent_storage.saveTask(task)
        return 1

    def __operatorRemoveTask(self,task):
        self.__operatorStopTask(task)
        task.remove()
        self.persistent_storage.removeTask(task)
        return 1

    def __taskFinder(self, task_id, operator):
        task = self.persistent_storage.getTask(task_id)
        if task:
            self.operatorGetTaskStatus(task)
            return operator(task)

    def updateTask(self, task):
        _logger.debug("Acquired session mutex: " + currentThread().getName())
        self.stateMutex.acquire()
        try:
            self.persistent_storage.saveTask(task)
        finally:
            self.stateMutex.release()
            _logger.debug("Released session mutex: " + currentThread().getName())

    def addTask(self, task):
        _logger.debug("Acquired session mutex: " + currentThread().getName())
        self.stateMutex.acquire()
        pid = task.get_os_pid()
        created_stopped = pid < 0
        try:
            if created_stopped:
                task.stop()
            if not created_stopped:
                # start pmmcli_daemon
                pmmcli_daemon_service.PMMCliDaemon().start()
            self.persistent_storage.saveTask(task)

        finally:
            self.stateMutex.release()
            _logger.debug("Released session mutex: " + currentThread().getName())


    def stopTask(self, task_id):
        _logger.debug("Acquired session mutex: " + currentThread().getName())
        self.stateMutex.acquire()
        try:
            result = self.__taskFinder(task_id, self.__stopTask)
        finally:
            self.stateMutex.release()
            _logger.debug("Released session mutex: " + currentThread().getName())
        return result

    def __stopTask(self, task):
        for child_task_id in task.get_child_task_ids():
            self.__taskFinder(child_task_id, self.__stopTask)
        return self.__operatorStopTask(task)

    def removeTask(self, task_id):
        _logger.debug("Acquired session mutex: " + currentThread().getName())
        self.stateMutex.acquire()
        try:
            result = self.__taskFinder(task_id,self.__operatorRemoveTask)
        finally:
            self.stateMutex.release()
            _logger.debug("Released session mutex: " + currentThread().getName())
        return result

    # the 'getXxxx' methods return XML-element-class instances autogenerated from 'pmm_api_xml_protocols.xsd'
    def getTaskList(self, types, owner_guids, topobject_id, topobject_type):
        task_list = []
        _logger.debug("Acquired session mutex: " + currentThread().getName())
        self.stateMutex.acquire()
        try:
            task_list = self.realGetTaskList(types, owner_guids, topobject_id, topobject_type)
        finally:
            self.stateMutex.release()
            _logger.debug("Released session mutex: " + currentThread().getName())
        task_object_list = TaskList.factory()
        for task_item in task_list:
            task_object = None
            dumps_storage_credentials_value = None
            owner_guid_value = None
            owner_type_value = None
            fullname_value = None
            name_value = None
            backup_profile_name_value = None
            mailto_value = None
            additional_info_value = None
            session_path = None
            if isinstance(task_item,BaseDictionaryTask):
                if task_item.has_key('dumps_storage_credentials'):
                    dumps_storage_credentials_value = task_item.get('dumps_storage_credentials')
                if task_item.has_key('owner_guid'):
                    owner_guid_value = task_item.get('owner_guid')
                if task_item.has_key('owner_type'):
                    owner_type_value = task_item.get('owner_type')
                if task_item.has_key('fullname'):
                    fullname_value = task_item.get('fullname')
                if task_item.has_key('name'):
                    name_value = task_item.get('name')
                if task_item.has_key('backup_profile_name'):
                    backup_profile_name_value = task_item.get('backup_profile_name')
                if task_item.has_key('mailto'):
                    mailto_value = task_item.get('mailto')
                if task_item.has_key('additional_info'):
                    additional_info_value = task_item.get('additional_info')
                if task_item.has_key('session_path'):
                    session_path = task_item.get('session_path')
            if dumps_storage_credentials_value:
                dumps_storage_credentials_formatter = pmm_dump_formatter.DumpsStorageCredentialsFormatter(dumps_storage_credentials_value)
                if not fullname_value and session_path:
                    dump_file_name = None
                    if dumps_storage_credentials_formatter.get_storage_type() in ['foreign-ftp', 'extension']:
                        dump_file_name = getPMMTaskManager().get_dump_file_name(os.path.join(session_path, 'export-dump-name'))
                    elif dumps_storage_credentials_formatter.get_storage_type() == 'local':
                        dump_file_name = getPMMTaskManager().get_dump_file_name(os.path.join(session_path, 'local-dump-name'))
                    if dump_file_name:
                        fullname_value = dump_file_name
                        name_value = os.path.basename(dump_file_name)
                task_object = TaskElement.factory(task_id=task_item.get_task_id(), task_type=task_item.get_tasktype(), name=name_value, mail_to=mailto_value, owner_guid=owner_guid_value, owner_type=owner_type_value, backup_profile_name=backup_profile_name_value, fullname=fullname_value, creation_date=task_item.get_creation_date_formatted(), task_status=self.operatorGetTaskStatus(task_item), dumps_storage_credentials=dumps_storage_credentials_formatter.buildXml(), additional_info=additional_info_value)
            else:
                task_object = TaskElement.factory(task_id=task_item.get_task_id(), task_type=task_item.get_tasktype(), name=name_value, mail_to=mailto_value, owner_guid=owner_guid_value, owner_type=owner_type_value, backup_profile_name=backup_profile_name_value, fullname=fullname_value, creation_date=task_item.get_creation_date_formatted(), task_status=self.operatorGetTaskStatus(task_item), additional_info=additional_info_value)
            task_object_list.add_task(task_object)
        return task_object_list

    def getTask(self, task_id):
        """
        :rtype: TaskList
        """
        _logger.debug("Acquired session mutex: " + currentThread().getName())
        self.stateMutex.acquire()
        try:
            result = self.__taskFinder(task_id,self.__operatorGetTask)
        finally:
            self.stateMutex.release()
            _logger.debug("Released session mutex: " + currentThread().getName())
        return result

    def getTaskStatus(self, task_id):
        _logger.debug("Acquired session mutex: " + currentThread().getName())
        self.stateMutex.acquire()
        try:
            result = self.__taskFinder(task_id,self.operatorGetTaskStatus)
        finally:
            self.stateMutex.release()
            _logger.debug("Released session mutex: " + currentThread().getName())
        return result

    def getTaskLog(self, task_id):
        _logger.debug("Acquired session mutex: " + currentThread().getName())
        self.stateMutex.acquire()
        try:
            result = self.__taskFinder(task_id,self.__operatorGetTaskLog)
        finally:
            self.stateMutex.release()
            _logger.debug("Released session mutex: " + currentThread().getName())
        return result

    def get_dump_file_name(self, dump_file_name_file_path):
        """
        :param str | unicode dump_file_name_file_path:
        :return str | unicode | None:
        """
        if not os.path.isfile(dump_file_name_file_path):
            return None
        with codecs.open(dump_file_name_file_path, 'r', encoding='utf-8') as dump_name_file:
            dump_file_name = dump_name_file.readline().rstrip().lstrip('/\\')
            return dump_file_name if dump_file_name else None

    def set_task_stopped(self, session_path):
        """
        :param str|unicode session_path:
        """
        with open(os.path.join(session_path, 'task_stopped'), 'w'):
            return
