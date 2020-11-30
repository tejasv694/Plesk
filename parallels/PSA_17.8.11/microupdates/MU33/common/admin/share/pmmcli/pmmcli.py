# Copyright 1999-2017. Plesk International GmbH. All rights reserved.
import os
import os.path
import log
import sys
import time
import libxml2
import logging
import cStringIO
import stacktrace
import base64
import json
from xml.dom import minidom
from xml.parsers.expat import ExpatError
from datetime import datetime

import pmm_task
import pmm_config
import plesk_config
import pmmcli_config
import pmmcli_session
import pmm_dump_formatter
import pmmcli_daemon_service
import pmm_repository_access_service
from pmmcli_exceptions import PMMUtilityException, PMMException, writeExecutionResult, \
    PMMAmbiguousObjectSpecifiedException, PMMObjectAbsentException
from pmmcli_daemon_actions import DaemonTaskDeleteTempDump
from pmm_api_xml_protocols import PlainData, DumpListQuery, Data, RestoreTaskDescription, \
RestoreTaskResult, TaskId, BackupTaskDescription, ResolveConflictsTaskDescription, Response, \
SrcDstFilesSpecification, CheckDumpResult, ConfigParameters, parameter, DeleteDumpQuery, ChildDumpsList, SessionId, \
RestoreTaskDescriptionSource
from pmm_utils import PmmUtils
from error_reporter import ErrorReporter

import osutil
import dirutil
import subproc
import validator
from encoder import EncoderFile

try:
    from hashlib import md5
except ImportError:
    from md5 import md5 as md5

mswindows = (sys.platform == "win32")

trace = True

_logger = logging.getLogger("pmm.pmmcli")


libxml2errorHandlerErr = ''
def libxml2errorHandler(ctx, str_):
    global libxml2errorHandlerErr
    libxml2errorHandlerErr = libxml2errorHandlerErr + "%s %s" % (ctx, str_)


def import_ftp_dump_to_file_dump(owner_type, owner_guid, src_dumps_specification, dst_dumps_specification=None):
    """Sub returns tuple (errcode, message, destination-dump-specification)
    errcode==0 on success, otherwise it contains errcode from failed export operation
    If dst_dumps_specification is None, then 'file' dump will be created in temp directory

    :param owner_type:
    :param owner_guid:
    :param src_dumps_specification:
    :param dst_dumps_specification:
    :return:
    """
    source_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(src_dumps_specification)
    source_dump_storage_credentials_formatter = source_dump_specification_formatter.get_dumps_storage_credentials_formatter()
    if source_dump_storage_credentials_formatter.get_storage_type() not in ['foreign-ftp', 'extension']:
        raise Exception("Source Dump Storage should be 'foreign-ftp' or 'extension' type")

    destination_dump_specification_formatter = None
    if dst_dumps_specification is not None:
        destination_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(dst_dumps_specification)
        if destination_dump_specification_formatter.get_dumps_storage_credentials_formatter().buildXml().get_storage_type() != 'file':
            raise Exception("Destination Dump Storage should be 'file' type")
    else:
        destination_dump_specification_formatter = pmm_dump_formatter.getTempFileDumpSpecificationFormatter()

    pmm_ras = pmm_repository_access_service.create(source_dump_storage_credentials_formatter)
    export_errcode, export_message = pmm_ras.export_file_as_file(destination_dump_specification_formatter, True)
    tmp_dst_file = destination_dump_specification_formatter.get_destination_file()
    _logger.debug(
        "Export '%s' dump to 'file' dump operation ended with errcode=%d (%s). Filename is %s"
        % (source_dump_storage_credentials_formatter.get_storage_type(), export_errcode, export_message, tmp_dst_file))
    if export_errcode != 0:
        return export_errcode, export_message, None
    if not os.path.isfile(tmp_dst_file):
        unknown_pmm_ras_error = 199
        unknown_pmm_ras_error_message = u"Unknown pmm-ras error: FTP dump was not exported to local file"
        return unknown_pmm_ras_error, unknown_pmm_ras_error_message, None
    # dump is exported into 'destination_dump_specification_formatter'
    return 0, export_message, destination_dump_specification_formatter

def getPleskObjectsStructure():
    cmd = subproc.CmdLine(pmm_config.backup_restore_helper())
    cmd.arg("--dump-objects-list")
    try:
        proc = cmd.spawn()
        return proc.stdout.encode('utf-8')
    except subproc.NonzeroExitException, x:
        _logger.debug("Getting of objects structure failed: " + x.subprocess.stdout)
        return None


def import_file_dump_to_local_repository(owner_type, owner_guid, src_dumps_specification, dst_dumps_specification, ignore_backup_password):
    """Sub returns tuple (errcode, message, destination-dump-specification)
    errcode==0|111 on success, otherwise it contains errcode from failed convertation
    retcode==111 if dump already exists in destination Dump Specification

    :param owner_type:
    :param owner_guid:
    :param src_dumps_specification:
    :param dst_dumps_specification:
    :param ignore_backup_password:
    :return:
    """
    source_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(src_dumps_specification)
    destination_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(dst_dumps_specification)

    if source_dump_specification_formatter.get_dumps_storage_credentials_formatter().buildXml().get_storage_type() != 'file':
        raise Exception("Source Dump Storage credentials should be 'file' type")
    if destination_dump_specification_formatter.get_dumps_storage_credentials_formatter().buildXml().get_storage_type() != 'local':
        raise Exception("Destination Dump Storage credentials should be 'local' type")

    ret_errcode = 0
    ret_message = u''
    objectsTreeFile = getPleskObjectsStructure()

    pmm_ras = pmm_repository_access_service.FtpRepositoryAccessService(source_dump_specification_formatter.get_dumps_storage_credentials_formatter())
    ret_errcode, ret_message, import_filename = pmm_ras.import_file_as_dump(source_dump_specification_formatter
                                                                            , destination_dump_specification_formatter
                                                                            , owner_guid, owner_type, None, None, None
                                                                            , ignore_backup_password, objectsTreeFile)

    try:
        pass
        if (objectsTreeFile is not None and os.path.isfile(objectsTreeFile)):
            os.remove(objectsTreeFile)
    except:
        pass

    _logger.debug("Import file operation ended with errcode=%d. Filename is %s" % (ret_errcode, import_filename))
    # error code 111 returned if such dump already exists in destination repository
    # error code 112 returned if object specified by guid and type has lower level than backup type
    # error code 113 returned if dump encryption failed
    # error code 116 returned if dump signature is wrong

    if ret_errcode == 113 and 'IGNORE_ENCRYPTION_ERRORS' in os.environ:
        ret_errcode = 0

    if ret_errcode != 0:
        if ret_errcode == 111:
            # replace message from utility with a special one
            ret_message = u"Dump already exists in repository"
        elif (ret_errcode != 116 or destination_dump_specification_formatter.get_dumps_storage_credentials_formatter().get_ignore_backup_sign() != 'true'):
            if ret_errcode == 112:
                # replace message from utility with a special one
                ret_message = unicode("The backup you are trying to import does not match the user type '%s'" % owner_type)
            else:
                # 'not-a-dump' format falls here
                pass
            if ret_errcode != 113:
                return ret_errcode, ret_message, None
    destination_dump_specification_formatter.set_name_of_xml_file(import_filename.lstrip('/'))
    # Dump of '9' format is imported into destination_dump_specification_formatter

    return ret_errcode, ret_message, destination_dump_specification_formatter

def setupBackupPasswordEnv(dump_storage_credentials):
    if dump_storage_credentials:
        if dump_storage_credentials.get_backup_password():
            os.environ['PLESK_BACKUP_CRYPT_KEY'] = str(dump_storage_credentials.get_backup_password())
        if dump_storage_credentials.get_backup_password_plain():
            os.environ['PLESK_BACKUP_PASSWORD'] = str(dump_storage_credentials.get_backup_password_plain())


class PmmcliException(Exception):
    def __init__(self):
        self.message = None


class PmmcliActionParamException(PmmcliException):
    def __init__(self, PmmcliAction, message):
        self.caller = PmmcliAction
        self.msg = message

    def get_message(self):
        return self.msg


class ActionRunner(object):
    def __init__(self, ActionProcessor,parameter_stdin = None, parameters = None):
        self.processor = ActionProcessor(parameter_stdin, parameters)

    def doActivity(self):
        libxml2.registerErrorHandler(libxml2errorHandler, '')
        input_valid, msg = self.processor.validate()
        if not input_valid:
            _logger.error("Validate failed: ActionProcessor " + str(self.processor))
            raise PmmcliActionParamException(self.processor, msg)
        result = None
        _logger.debug(str(self) + ": doActivity")
        result = self.processor.doActivity()
        return self.processor.response(result)

def maskPassword(xmlstr):
    try:
        def replaceChildNode(node):
            for child in node.childNodes[:]:
                node.removeChild(child)
            node.appendChild(doc.createTextNode('*****'))

        doc = minidom.parseString(xmlstr)
        for node in doc.getElementsByTagName('password'):
            replaceChildNode(node)
        for node in doc.getElementsByTagName('backup-password-plain'):
            replaceChildNode(node)
        for node in doc.getElementsByTagName('env'):
            nameAttr = node.attributes['name']
            if nameAttr and nameAttr.value == 'DUMP_STORAGE_PASSWD':
                replaceChildNode(node)
        return doc.toxml()
    except ExpatError:
        return "<invalid xml>"

class PMMCliAction(object):
    def __init__(self, parameter_stdin, parameters):
        _logger.debug("--> " + str(self))
        if parameters:
            _logger.info("parameters: " + str(parameters))
        if parameter_stdin:
            _logger.info("stdin: " + maskPassword(parameter_stdin))
        self._stdin = parameter_stdin
        self._params = parameters
        self._process = None
        self.__input_validator = validator.Validator(pmm_config.pmm_api_xml_protocols_schema())

    def get_input_validator(self):
        return self.__input_validator

    def validate(self):
        _logger.debug(str(self) + ": validate")
        return 1

    def doActivity(self):
        raise PmmcliException()

    def response(self, result):
        _logger.debug(str(self) + ": response")
        return result

    def setProcess(self, process):
        if process == None:
            _logger.debug("Subprocess finished")
        else:
            _logger.debug(u"Subprocess " + unicode(process) + u" encountered.")
            self._process = process

    def unsetProcess(self, process):
        self.setProcess(None)

    def initLog(self, session, category, stage):
        # Path to log file consists of two parts: path to logs directory and
        # log file name.
        # Path to log directory is put to 'CUSTOM_LOG_DIR' environment variable
        # and is not overridden if it already exists. It is needed to put logs
        # from restore task started during migration process to directory with
        # migration logs.
        # Path to log file is put to 'CUSTOM_LOG' and is temporary overridden.
        _logger.debug("LOG: init '%s-%s' (with session=%s)", category, stage, (session is not None))
        if 'CUSTOM_LOG_DIR' in os.environ:
            logs_dir = os.environ['CUSTOM_LOG_DIR']
            _logger.debug("LOG: CUSTOM_LOG_DIR is defined: %s", logs_dir)
        else:
            logs_dir = session.get_logs_dir() if session is not None else None
            if logs_dir is None:
                _logger.debug("LOG: no logs dir in session, create")
                # Generate logs directory name.
                logs_dir = PMMCliAction._generate_logs_dir_path(category)
                while not dirutil.mkdirs(logs_dir, 0750, -1, plesk_config.psaadm_gid()):
                    time.sleep(0.001)
                    logs_dir = PMMCliAction._generate_logs_dir_path(category)

            _logger.debug("LOG: log dir %s", logs_dir)
            os.environ['CUSTOM_LOG_DIR'] = logs_dir

        self.__logs_dir = logs_dir

        log_path = os.path.join(logs_dir, "%s.log" % (stage,))
        if not mswindows:
            with open(log_path, 'wt'):
                pass
            os.chmod(log_path, 0660)
            os.chown(log_path, -1, plesk_config.psaadm_gid())

        os.environ["CUSTOM_LOG"] = log_path
        log.initPidLog("pmmcli", logs_dir, True)
        _logger.debug("LOG: custom log %s", log_path)

    def getLogsDir(self):
        _logger.debug("LOG: logs dir requested: %s", self.__logs_dir)
        return self.__logs_dir

    @staticmethod
    def _generate_logs_dir_path(category):
        now = datetime.now()
        logs_dir_name = "%s-%s-%03d" % (category, now.strftime("%Y-%m-%d-%H-%M-%S"), now.microsecond/1000)
        return os.path.join(pmm_config.pmm_logs_directory(), logs_dir_name)

class GetDumpsListAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__parameters = parameters
        self.__dump_list_query = None
        self.__lightweight = True

    def validate(self):
        try:
            error_code, msg = self.get_input_validator().do(self.__parameter_stdin)
        except libxml2.parserError, ex:
            return None, 'XML parse error: ' + ex.msg + '\n' + libxml2errorHandlerErr
        if error_code:
            return None, 'Error ' + str(error_code) + ': ' + msg
        try:
            self.__dump_list_query = DumpListQuery.factory()
            self.__dump_list_query.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        except ExpatError:
            return None, 'ExpatError in DumpListQuery'
        self.__lightweight = '-lightweight-mode' in self.__parameters
        return 1, ''

    def doActivity(self):
        if not self.__dump_list_query:
            self.__dump_list_query = DumpListQuery.factory()
            self.__dump_list_query.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        dumps_storage_credentials = self.__dump_list_query.get_dumps_storage_credentials()
        object_specification =  self.__dump_list_query.get_object_specification()
        access_service = pmm_repository_access_service.create(dumps_storage_credentials)
        result_dump_list, errcode, message = access_service.getDumpList(object_specification, self.__lightweight)
        data = Data.factory( dump_list = result_dump_list )
        return data, errcode, message


class GetDumpOverviewAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__session_id = None
        self.__dump_specification_formatter = None

    def validate(self):
        if self.__parameter_stdin:
            try:
                self.__dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(self.__parameter_stdin)
            except pmm_dump_formatter.DumpSpecificationFormatException:
                # parameter_stdin is session-id
                self.__session_id = self.__parameter_stdin
        elif 0 < len(self._params):
            self.__session_id = self._params[0]
        else:
            return None, "Dump specification or session ID are not specified"
        return 1, ''

    def doActivity(self):
        session = None
        if self.__dump_specification_formatter:
            self.initLog(None, 'restore', 'import')

            storage_type = self.__dump_specification_formatter.get_dumps_storage_credentials_formatter().get_storage_type()
            if storage_type == 'backup-node':
                try:
                    destination_dump_specification_formatter = pmm_dump_formatter.getLocalDumpSpecificationFormatter('dummy.xml')
                    pmm_ras = pmm_repository_access_service.LocalRepositoryAccessService(self.__dump_specification_formatter.get_dumps_storage_credentials_formatter())
                    child_dumps_list, errcode, message = pmm_ras._pmmras_get_child_dumps(self.__dump_specification_formatter.get_dumps_storage_credentials_formatter().getDumpStorage(), self.__dump_specification_formatter.get_name_of_xml_file(), destination_dump_specification_formatter.get_dumps_storage_credentials_formatter().getDumpStorage())
                    if errcode != 0:
                        return None, errcode, message

                    destination_dump_specification_formatter.set_name_of_xml_file(self.__dump_specification_formatter.get_name_of_xml_file().lstrip('/'))

                    errcode, message = pmm_ras.convert_local_dump(destination_dump_specification_formatter)
                    if errcode != 0:
                        return None, errcode, message
                    session = pmmcli_session.PmmcliSession.createSession('','', destination_dump_specification_formatter.get_destination_file(), destination_dump_specification_formatter.buildString(), True, self.__dump_specification_formatter.buildString())
                    session.set_logs_dir(self.getLogsDir())
                except pmmcli_session.RSessionParamException, x:
                    return None, 1, "Could not create restore session. Invalid parameter: " + x.get_message()

            if storage_type == 'local':
                try:
                    pmm_ras = pmm_repository_access_service.LocalRepositoryAccessService(self.__dump_specification_formatter.get_dumps_storage_credentials_formatter())
                    errcode, message = pmm_ras.convert_local_dump(self.__dump_specification_formatter)
                    if errcode != 0:
                        return None, errcode, message
                    session = pmmcli_session.PmmcliSession.createSession('','',self.__dump_specification_formatter.get_destination_file(), self.__dump_specification_formatter.buildString(), True)
                    session.set_logs_dir(self.getLogsDir())
                except pmmcli_session.RSessionParamException, x:
                    return None, 1, "Could not create restore session. Invalid parameter: " + x.get_message()
        else:
            #get dump from session dir
            session = pmmcli_session.PmmcliSession.openSession(self.__session_id)
            self.initLog(session, 'restore', 'import')

        # start pmmcli_daemon
        pmmcli_daemon_service.PMMCliDaemon().start()
        # initialize dump overview maker and get dump-overview
        dump_overview_object = session.getDumpOverview()
        errcode = 0
        data = Data.factory( dump_overview = dump_overview_object)
        return data, errcode, None


class CheckDumpAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__dump_specification_formatter = None

    def validate(self):
        try:
            error_code, msg = self.get_input_validator().do(self.__parameter_stdin)
        except libxml2.parserError, ex:
            return None, 'XML parse error: ' + ex.msg + '\n' + libxml2errorHandlerErr
        if error_code:
            return None, 'Error ' + str(error_code) + ': ' + msg
        try:
            self.__dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(self.__parameter_stdin)
        except pmm_dump_formatter.DumpSpecificationFormatException:
            return None, 'DumpSpecificationFormatException in DumpSpecification'
        return 1, ''

    def doActivity(self):
        if not self.__dump_specification_formatter:
            self.__dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(self.__parameter_stdin)
        dumps_storage_credentials_formatter = self.__dump_specification_formatter.get_dumps_storage_credentials_formatter()
        data = None
        access_service = pmm_repository_access_service.create(dumps_storage_credentials_formatter)
        dump_result, errcode, message = access_service.getDumpStatus(self.__dump_specification_formatter)
        if dump_result:
            dump_status_array = dump_result.get_dump_status()
            check_dump_result_object = CheckDumpResult.factory(dump_status=dump_status_array)
            data = Data.factory(check_dump_result=check_dump_result_object)
        return data, errcode, message


class ExportDumpAsFileAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__parameters = parameters
        self.__src_dst_files_specification = None
        self.__temp_dump = False
        self.__include_increments = False

    def validate(self):
        try:
            error_code, msg = self.get_input_validator().do(self.__parameter_stdin)
        except libxml2.parserError, ex:
            return None, 'XML parse error: ' + ex.msg + '\n' + libxml2errorHandlerErr
        if error_code:
            return None, 'Error ' + str(error_code) + ': ' + msg
        try:
            self.__src_dst_files_specification = SrcDstFilesSpecification.factory()
            self.__src_dst_files_specification.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
            if not self.__src_dst_files_specification.get_src() or not self.__src_dst_files_specification.get_dst():
                return None, "SrcDstFilesSpecification has wrong format"
            if self.__src_dst_files_specification.get_src().get_dumps_storage_credentials().get_storage_type() != 'local':
                return None, "ExportDumpAsFileAction called for '" + self.__src_dst_files_specification.get_src().get_dumps_storage_credentials().get_storage_type() + "' storage type"
        except ExpatError:
            return None, 'ExpatError in SrcDstFilesSpecification'
        self.__temp_dump = '-temp' in self.__parameters
        self.__include_increments = '-include-increments' in self.__parameters
        return 1, ''

    def addDaemonTaskDeleteTempDump(self, dump_specification_formatter):
        task_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(dump_specification_formatter)
        daemon_task = DaemonTaskDeleteTempDump(task_dump_specification_formatter)
        try:
            daemon_task.save()
        except Exception, e:
            _logger.error("Exception %s occured during save daemon task to delete temp dump: %s\n Call stack is:\n%s" %(str(e.__class__),str(e),stacktrace.stacktrace()))

    def doActivity(self):
        self.initLog(None, 'export', 'export')

        if not self.__src_dst_files_specification:
            self.__src_dst_files_specification = SrcDstFilesSpecification.factory()
            self.__src_dst_files_specification.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        src_dumps_specification = self.__src_dst_files_specification.get_src()
        dst_dumps_specification = self.__src_dst_files_specification.get_dst()
        source_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(src_dumps_specification)
        destination_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(dst_dumps_specification)
        destination_dumps_storage_credentials_formatter = destination_dump_specification_formatter.get_dumps_storage_credentials_formatter()

        delete_temp_dump = False

        storage_type = destination_dumps_storage_credentials_formatter.get_storage_type()
        if storage_type == 'foreign-ftp':
            ftp_password = destination_dumps_storage_credentials_formatter.get_password()
            os.environ['FTP_PASSWORD'] = str(ftp_password)
        elif storage_type == 'file':
            if self.__temp_dump:
                delete_temp_dump = True

        setupBackupPasswordEnv(self.__src_dst_files_specification.get_dst().get_dumps_storage_credentials())
        if mswindows:
            source_dump_storage_credentials = source_dump_specification_formatter.get_dumps_storage_credentials_formatter().buildXml()
            pmm_ras = pmm_repository_access_service.FtpRepositoryAccessService(source_dump_storage_credentials)
            errcode, message, filename = pmm_ras.export_dump_as_file(source_dump_specification_formatter,destination_dump_specification_formatter,self.__include_increments)
            return None, errcode, message

        else:
            cmd = subproc.CmdLine(pmm_config.backup(), self)
            for backup_arg in pmm_config.plesk_backup_args():
                cmd.arg(backup_arg)
            cmd.arg('export-dump-as-file')
            dump_file_name_arg = '--dump-file-name=' + source_dump_specification_formatter.get_destination_file()
            cmd.arg(dump_file_name_arg)
            # output file format is one of these:
            # ftp://login@hostname/root_dir/file_name
            # /root_dir/file_name
            output_file_arg = '--output-file=' + destination_dump_specification_formatter.get_destination_file()
            cmd.arg(output_file_arg)

            cmd.arg('--no-gzip')

            use_passive_ftp_mode = destination_dumps_storage_credentials_formatter.get_use_passive_ftp_mode()
            if use_passive_ftp_mode == 'true':
                cmd.arg('--ftp-passive-mode')

            if self.__include_increments:
                cmd.arg('--include-increments')

            errcode = 0
            message = u''
            try:
                cmd.spawn()
                #message = u"== STDOUT ====================\n" + proc.stdout + u"\n==============================\n" + u"== STDERR ====================\n" + proc.stderr + u"\n==============================\n"
            except subproc.NonzeroExitException, x:
                errcode = x.exitcode
                message =  u"export-dump-as-file error (Error code = " + unicode(errcode) + "):"  \
                         + u"\n== STDOUT ====================\n" + x.subprocess.stdout + u"\n==============================\n" \
                         + u"== STDERR ====================\n" + x.subprocess.stderr + u"\n==============================\n"
            if delete_temp_dump:
                self.addDaemonTaskDeleteTempDump(destination_dump_specification_formatter)
                pmmcli_daemon_service.PMMCliDaemon().start()
            return None, errcode, message


class ExportFileAsFileAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__parameters = parameters
        self.__src_dst_files_specification = None

    def validate(self):
        try:
            error_code, msg = self.get_input_validator().do(self.__parameter_stdin)
        except libxml2.parserError, ex:
            return None, 'XML parse error: ' + ex.msg + '\n' + libxml2errorHandlerErr
        if error_code:
            return None, 'Error ' + str(error_code) + ': ' + msg
        try:
            self.__src_dst_files_specification = SrcDstFilesSpecification.factory()
            self.__src_dst_files_specification.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
            if not self.__src_dst_files_specification.get_src() or not self.__src_dst_files_specification.get_dst():
                return None, "SrcDstFilesSpecification has wrong format"
            if self.__src_dst_files_specification.get_src().get_dumps_storage_credentials().get_storage_type() == 'local':
                return None, "ExportFileAsFileAction called for 'local' source storage type"
            if self.__src_dst_files_specification.get_dst().get_dumps_storage_credentials().get_storage_type() == 'local':
                return None, "ExportFileAsFileAction called for 'local' destination storage type"
        except ExpatError:
            return None, 'ExpatError in SrcDstFilesSpecification'
        return 1, ''

    def doActivity(self):
        self.initLog(None, 'export', 'export')

        if not self.__src_dst_files_specification:
            self.__src_dst_files_specification = SrcDstFilesSpecification.factory()
            self.__src_dst_files_specification.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        src_dumps_specification = self.__src_dst_files_specification.get_src()
        dst_dumps_specification = self.__src_dst_files_specification.get_dst()
        source_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(src_dumps_specification)
        destination_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(dst_dumps_specification)
        source_dump_storage_credentials = source_dump_specification_formatter.get_dumps_storage_credentials_formatter().buildXml()
        pmm_ras = pmm_repository_access_service.FtpRepositoryAccessService(source_dump_storage_credentials)
        join_volumes = False
        if len(self.__parameters) > 0 and self.__parameters[0] == "-join-volumes":
            join_volumes = True
        errcode, message = pmm_ras.export_file_as_file(destination_dump_specification_formatter, join_volumes)
        return None, errcode, message


class ImportFileAsDumpAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__parameters = parameters
        self.__src_dst_files_specification = None
        self.__include_increments = False

    def validate(self):
        try:
            error_code, msg = self.get_input_validator().do(self.__parameter_stdin)
        except libxml2.parserError, ex:
            return None, 'XML parse error: ' + ex.msg + '\n' + libxml2errorHandlerErr
        if error_code:
            return None, 'Error ' + str(error_code) + ': ' + msg
        try:
            self.__src_dst_files_specification = SrcDstFilesSpecification.factory()
            self.__src_dst_files_specification.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
            if not self.__src_dst_files_specification.get_src() or not self.__src_dst_files_specification.get_dst():
                return None, "SrcDstFilesSpecification has wrong format"
            if self.__src_dst_files_specification.get_dst().get_dumps_storage_credentials().get_storage_type() != 'local':
                return None, "ExportDumpAsFileAction called for '" + self.__src_dst_files_specification.get_dst().get_dumps_storage_credentials().get_storage_type() + "' storage type"
            owner_guid = self.__src_dst_files_specification.get_guid()
            owner_type = self.__src_dst_files_specification.get_type()
            if owner_type == None:
                return None, "SrcDstFilesSpecification has wrong format: 'type' attribute is required for '--import-file-as-dump' command'"
            if owner_guid == None:
                return None, "SrcDstFilesSpecification has wrong format: 'guid' attribute is required for '--import-file-as-dump' command'"
            if owner_guid == '':
                return None, "SrcDstFilesSpecification has wrong format: not-empty 'guid' attribute is required for '--import-file-as-dump' command'"
        except ExpatError:
            return None, 'ExpatError in SrcDstFilesSpecification'
        self.__include_increments = '-include-increments' in self.__parameters
        return 1, ''

    def doActivity(self):
        self.initLog(None, 'import', 'import')

        if not self.__src_dst_files_specification:
            self.__src_dst_files_specification = SrcDstFilesSpecification.factory()
            self.__src_dst_files_specification.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        src_dumps_specification = self.__src_dst_files_specification.get_src()
        dst_dumps_specification = self.__src_dst_files_specification.get_dst()
        owner_guid = self.__src_dst_files_specification.get_guid()
        owner_type = self.__src_dst_files_specification.get_type()

        setupBackupPasswordEnv(self.__src_dst_files_specification.get_src().get_dumps_storage_credentials())
        ignore_backup_password = self.__src_dst_files_specification.get_src().get_dumps_storage_credentials().get_ignore_backup_password()

        source_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(src_dumps_specification)
        destination_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(dst_dumps_specification)

        ignored_import_errors = [0, 111, 113]
        if destination_dump_specification_formatter.get_dumps_storage_credentials_formatter().get_ignore_backup_sign() == 'true':
            ignored_import_errors.append(116)

        storage_type = source_dump_specification_formatter.get_dumps_storage_credentials_formatter().get_storage_type()
        files_for_import = []
        if self.__include_increments:
            pmm_ras = pmm_repository_access_service.create(src_dumps_specification.get_dumps_storage_credentials())
            src_dump_result, errcode, message = pmm_ras.getDumpStatus(src_dumps_specification)
            if 0 == errcode:
                if src_dump_result.get_related_dumps():
                    for related_dump in src_dump_result.get_related_dumps().get_related_dump():
                        related_dump_file_name = src_dumps_specification.get_dumps_storage_credentials().get_file_name().replace(
                            '_%s' % src_dump_result.get_creation_date()
                            , '' if src_dump_result.get_increment_base() == related_dump else '_%s' % related_dump
                        )
                        files_for_import.append(related_dump_file_name)
        files_for_import.append(src_dumps_specification.get_dumps_storage_credentials().get_file_name())

        ret_message = None
        dump_result = None
        check_errcode = 0
        import_errcode = 0
        for file_for_import in files_for_import:
            src_dumps_specification.get_dumps_storage_credentials().set_file_name(file_for_import)
            tmp_file_dump_specification_formatter = None

            if storage_type in ['foreign-ftp', 'extension']:
                importftp_errcode, importftp_message, tmp_file_dump_specification_formatter = \
                    import_ftp_dump_to_file_dump(owner_type, owner_guid, src_dumps_specification)
                if importftp_errcode == 151: # dump does not exist
                    continue
                if importftp_errcode != 0:
                    return None, importftp_errcode, importftp_message
                source_dump_specification_formatter = tmp_file_dump_specification_formatter
            else:
                source_dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(src_dumps_specification)

            import_errcode, import_message, imported_dump_specification_formatter = \
                import_file_dump_to_local_repository(owner_type, owner_guid, source_dump_specification_formatter
                                                     , destination_dump_specification_formatter
                                                     , ignore_backup_password == 'true')

            if tmp_file_dump_specification_formatter is not None:
                # remove temporary dump of 'file' format
                _logger.debug("Remove temporary dump: %s" % tmp_file_dump_specification_formatter.get_destination_file())
                pmm_ras = pmm_repository_access_service.FtpRepositoryAccessService(tmp_file_dump_specification_formatter.get_dumps_storage_credentials_formatter())
                errcode, message = pmm_ras.delete_dump(tmp_file_dump_specification_formatter)
                _logger.debug("Remove dump operation ended with errcode=%d (%s)" % (errcode, message))

            if import_errcode == 151: # dump does not exist
                continue
            if import_errcode not in ignored_import_errors:
                return None, import_errcode, import_message

            if ret_message and len(ret_message) > 0:
                ret_message = ret_message + "\n" + unicode(import_message)
            else:
                ret_message = unicode(import_message)

            destination_dumps_storage_credentials_formatter = imported_dump_specification_formatter.get_dumps_storage_credentials_formatter()
            dump_result, check_errcode, check_message = pmm_repository_access_service.LocalRepositoryAccessService(destination_dumps_storage_credentials_formatter).getDumpStatus(imported_dump_specification_formatter)
            _logger.debug("Check dump operation ended.")
            if check_message:
                ret_message = ret_message + u"pmm-ras check dump error:\n" + unicode(check_message) + dump_result

        data = Data.factory(dump=dump_result)

        if check_errcode == 0 and import_errcode in ignored_import_errors:
            ret_errcode = import_errcode
        else:
            ret_errcode = check_errcode

        return data, ret_errcode, ret_message


class RestoreAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__restore_task_description = None
        self.__parameters = parameters
        self.__fix_names = None

    def validate(self):
        try:
            error_code, msg = self.get_input_validator().do(self.__parameter_stdin)
        except libxml2.parserError, ex:
            return None, 'XML parse error: ' + ex.msg + '\n' + libxml2errorHandlerErr
        if error_code:
            return None, 'Error ' + str(error_code) + ': ' + msg
        try:
            self.__restore_task_description = RestoreTaskDescription.factory()
            self.__restore_task_description.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        except ExpatError:
            return None, 'ExpatError in RestoreTaskDescription'
        owner_type = self.__restore_task_description.get_owner_type()
        if owner_type not in ['server', 'reseller', 'client', 'domain']:
            return None, "Could not restore dump: owner-type must be one of 'server','reseller','client','domain'"
        return 1, ''

    def __get_conflict_resolve_task(self, session, dump_specification_formatter, create):
        conflict_resolve_task = None
        restore_task = self.__get_restore_task(session, dump_specification_formatter)
        conflict_resolve_task_id = restore_task.get('conflict_resolve_task_id')
        if conflict_resolve_task_id is not None:
            conflict_resolve_task = pmm_task.getPMMTaskManager().realGetTask(conflict_resolve_task_id)
            conflict_resolve_task.set('session_path', session.get_session_path())
            pmm_task.getPMMTaskManager().updateTask(conflict_resolve_task)
        elif create:
            conflict_resolve_task = pmm_task.ConflictResolveTask({'session_path': session.get_session_path()})
            restore_task.set('conflict_resolve_task_id', conflict_resolve_task.get_task_id())
            pmm_task.getPMMTaskManager().updateTask(restore_task)
        return conflict_resolve_task

    def __get_restore_task(self, session, dump_specification_formatter):
        task_id = session.get_task_id()
        if task_id is not None:
            restore_task = pmm_task.getPMMTaskManager().realGetTask(task_id)
            lock_file_name = restore_task.try_get('lock-file-name')
            if lock_file_name is not None:
                while not os.path.isfile(lock_file_name):
                    _logger.debug('Wait for %s creation' % lock_file_name)
                    time.sleep(0.25)
                restore_task = pmm_task.getPMMTaskManager().realGetTask(task_id)
            restore_task.set_os_pid(os.getpid())
        else:
            dumps_storage_credentials_formatter = dump_specification_formatter.get_dumps_storage_credentials_formatter()
            storage_full_name = dumps_storage_credentials_formatter.getDumpStorage()
            name = session.get_info_xml_base().replace(storage_full_name, '')
            restore_task_params = {
                'name': name,
                'fullname': name,
                'conflict_resolve_task_id': None,
                'deploy_task_id': None,
                'owner_guid': self.__restore_task_description.get_owner_guid(),
                'owner_type': self.__restore_task_description.get_owner_type(),
                'owner_name': self.__restore_task_description.get_owner_name(),
                'topobject_id': self.__restore_task_description.get_top_object_id(),
                'topobject_type': self.__restore_task_description.get_top_object_type(),
                'topobject_name': self.__restore_task_description.get_top_object_name(),
                'topobject_guid': self.__restore_task_description.get_top_object_guid(),
                'dumps_storage_credentials': dumps_storage_credentials_formatter.buildString()
            }

            restore_task = pmm_task.RestoreTask(osutil.get_cmd(os.getpid()), os.getpid(), restore_task_params)
            session.set_task_id(restore_task.get_task_id())
        restore_task.set('session_path', session.get_session_path())
        pmm_task.getPMMTaskManager().updateTask(restore_task)
        return restore_task

    def __delete_dump(self, dump_specification_formatter):
        _logger.info("Delete imported dump from local repository")
        access_service = pmm_repository_access_service.LocalRepositoryAccessService
        dumps_storage_credentials = dump_specification_formatter.get_dumps_storage_credentials_formatter()
        errcode, message = access_service(dumps_storage_credentials).delete_dump(dump_specification_formatter.buildXml())
        _logger.debug("Deletion result: %s, %s", errcode, message)

    def _clone_session(self):
        # create new session based on selected objects
        session_id = self.__restore_task_description.get_source().get_session_id()
        session = pmmcli_session.PmmcliSession.cloneSession(session_id)
        dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(
            session.get_dump_specification_value())
        return dump_specification_formatter, session

    def __import_full_backup_from_ftp(self, content_only, dump_specification_formatter, import_base=None):
        d_s = dump_specification_formatter.buildXml()
        storage_type = dump_specification_formatter.get_dumps_storage_credentials_formatter().get_storage_type()

        if storage_type not in ('foreign-ftp', 'extension'):
            raise Exception('The method supports foreign-ftp storage type only')

        restore_task_description_misc = self.__restore_task_description.get_misc()
        owner_guid = self.__restore_task_description.get_owner_guid()
        owner_type = self.__restore_task_description.get_owner_type()
        object_type = self.__restore_task_description.get_top_object_type()
        object_guid = self.__restore_task_description.get_top_object_guid()
        object_name = self.__restore_task_description.get_top_object_name()
        ignore_sign, ignore_conflicts, ignore_backup_password = self.__get_ignore_flags()

        # error code 111 returned if such dump already exists in destination repository
        # error code 112 returned if object specified by guid and type have no rights to import dump
        # other error code could indicate an error
        # anyway we should continue only if errcode is 0 or 111
        ignored_import_errors = [0, 111, 151]
        if ignore_sign == 1:
            ignored_import_errors.append(116)

        pmm_ras = pmm_repository_access_service.FtpRepositoryAccessService(dump_specification_formatter.get_dumps_storage_credentials_formatter())
        ftp_dump_result, errcode, message = pmm_ras.getDumpStatus(dump_specification_formatter)
        if errcode not in ignored_import_errors:
            return None, errcode, message

        dumps_for_import = []
        if ftp_dump_result.get_related_dumps():
            dumps_for_import = ftp_dump_result.get_related_dumps().get_related_dump()
        dumps_for_import.append(ftp_dump_result.get_creation_date())

        objects_tree_file = getPleskObjectsStructure()
        setupBackupPasswordEnv(d_s.get_dumps_storage_credentials())
        imported_dump_specification_formatter = None
        for dump_for_import in dumps_for_import:
            dump_for_import_file_name = ftp_dump_result.get_name().replace(
                '_%s' % ftp_dump_result.get_creation_date()
                , '' if ftp_dump_result.get_increment_base() == dump_for_import else '_%s' % dump_for_import
            )
            d_s.get_dumps_storage_credentials().set_file_name(dump_for_import_file_name)
            imported_dump_specification_formatter = pmm_dump_formatter.getLocalDumpSpecificationFormatter('dummy.xml')
            if ignore_sign == 1:
                imported_dump_specification_formatter.get_dumps_storage_credentials_formatter().set_ignore_backup_sign('true')
            setupBackupPasswordEnv(d_s.get_dumps_storage_credentials())
            pmm_ras = pmm_repository_access_service.FtpRepositoryAccessService(d_s.get_dumps_storage_credentials())
            errcode, message, import_filename = pmm_ras.import_file_as_dump(d_s, imported_dump_specification_formatter
                                                                            , owner_guid, owner_type, object_type
                                                                            , object_guid, object_name
                                                                            , ignore_backup_password, objects_tree_file
                                                                            , False
                                                                            , content_only and ftp_dump_result.get_creation_date() == dump_for_import
                                                                            , import_base)
            if errcode not in ignored_import_errors:
                return None, errcode, message
            imported_dump_specification_formatter.set_name_of_xml_file(import_filename)
            dump_is_temporary = errcode != 111 and restore_task_description_misc is not None and restore_task_description_misc.get_delete_downloaded_dumps() == 'true'

            # call check dump for imported dump
            imported_dump_storage_credentials_formatter = imported_dump_specification_formatter.get_dumps_storage_credentials_formatter()
            dump_result, check_errcode, check_message = pmm_repository_access_service.LocalRepositoryAccessService(imported_dump_storage_credentials_formatter).getDumpStatus(imported_dump_specification_formatter)
            _logger.debug("Check dump operation ended (%s)" % check_message)
            # check_errcode is always None
            # we should analyse dump_result to get dump status
            status_array = dump_result.get_dump_status()
            dump_failed_on_status = False
            for status in status_array:
                _logger.debug("Import dump status: %s" % status.get_dump_status())
                if status.get_dump_status() in ['WRONG-FORMAT', 'CONTENT-ERROR']:
                    dump_failed_on_status = True

            result_message = u''
            if dump_failed_on_status:
                if message:
                    result_message = u"pmm-ras check dump error:\n" + unicode(message)
                if dump_is_temporary:
                    self.__delete_dump(imported_dump_specification_formatter)
                return None, 14, u"Check imported dump " + unicode(imported_dump_specification_formatter.get_destination_file()) + u" failed.\n" + result_message
        return imported_dump_specification_formatter, 0, None

    def __init_rsession(self):
        imported_dump_specification_formatter = None
        d_s = self.__restore_task_description.get_source().get_dump_specification()
        prev_session_id = self.__restore_task_description.get_source().get_session_id()
        prev_session = None
        if prev_session_id:
            prev_session = pmmcli_session.PmmcliSession(prev_session_id)
        self.initLog(prev_session, 'restore', 'deployer')

        # source is dump specification so we have to create new session
        dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(d_s)
        # do not change dump_specification_formatter after it have been initialize, it should contain DS from restore task description

        storage_type = dump_specification_formatter.get_dumps_storage_credentials_formatter().get_storage_type()
        restore_task_description_misc = self.__restore_task_description.get_misc()
        owner_guid = self.__restore_task_description.get_owner_guid()
        owner_type = self.__restore_task_description.get_owner_type()
        object_type = self.__restore_task_description.get_top_object_type()
        object_guid = self.__restore_task_description.get_top_object_guid()
        object_name = self.__restore_task_description.get_top_object_name()
        ignore_sign, ignore_conflicts, ignore_backup_password = self.__get_ignore_flags()

        if storage_type == 'local':
            try:
                pmm_ras = pmm_repository_access_service.LocalRepositoryAccessService(dump_specification_formatter.get_dumps_storage_credentials_formatter())
                errcode, message = pmm_ras.convert_local_dump(dump_specification_formatter)
                if errcode != 0:
                    return None, errcode, message
                session = pmmcli_session.PmmcliSession.createSession(owner_type, owner_guid, dump_specification_formatter.get_destination_file(), dump_specification_formatter.buildString(), True)
                session.set_logs_dir(self.getLogsDir())
            except pmmcli_session.RSessionParamException, x:
                return None, 1, "Could not create restore session. Invalid parameter: " + x.get_message()
        else:  # storage_type == 'foreign-ftp':
            objects_tree_file = getPleskObjectsStructure()
            imported_dump_specification_formatter = pmm_dump_formatter.getLocalDumpSpecificationFormatter('dummy.xml')
            ignored_import_errors = [0, 2, 111]
            if ignore_sign == 1:
                ignored_import_errors.append(116)
                imported_dump_specification_formatter.get_dumps_storage_credentials_formatter().set_ignore_backup_sign('true')
            setupBackupPasswordEnv(d_s.get_dumps_storage_credentials())
            pmm_ras = pmm_repository_access_service.FtpRepositoryAccessService(dump_specification_formatter.get_dumps_storage_credentials_formatter())
            errcode, message, import_filename = pmm_ras.import_file_as_dump(dump_specification_formatter
                                                                            , imported_dump_specification_formatter
                                                                            , owner_guid, owner_type, object_type
                                                                            , object_guid, object_name
                                                                            , ignore_backup_password, objects_tree_file
                                                                            , True)
            if errcode not in ignored_import_errors:
                return None, errcode, message
            if errcode == 2:
                lazy_import = 0
                imported_dump_specification_formatter, errcode, message = self.__import_full_backup_from_ftp(False, dump_specification_formatter)
                if errcode != 0:
                    return None, errcode, message
            else:
                lazy_import = 1
                imported_dump_specification_formatter.set_name_of_xml_file(import_filename)
            dump_is_temporary = errcode != 111 and restore_task_description_misc is not None and restore_task_description_misc.get_delete_downloaded_dumps() == 'true'

            # call check dump for imported dump
            imported_dump_storage_credentials_formatter = imported_dump_specification_formatter.get_dumps_storage_credentials_formatter()
            dump_result, check_errcode, check_message = pmm_repository_access_service.LocalRepositoryAccessService(
                imported_dump_storage_credentials_formatter).getDumpStatus(imported_dump_specification_formatter)
            _logger.debug("Check dump operation ended (%s)" % check_message)

            status_array = dump_result.get_dump_status()
            dump_failed_on_status = False
            for status in status_array:
                _logger.debug("Import dump status: %s" % status.get_dump_status())
                if status.get_dump_status() in ['WRONG-FORMAT']:
                    dump_failed_on_status = True

            result_message = u''
            if dump_failed_on_status:
                if message:
                    result_message = u"pmm-ras check dump error:\n" + unicode(message)
                if dump_is_temporary:
                    self.__delete_dump(imported_dump_specification_formatter)
                return None, 14, u"Check imported dump " + unicode(imported_dump_specification_formatter.get_destination_file()) + u" failed.\n" + result_message

            # dump is imported and checked
            try:
                session = pmmcli_session.PmmcliSession.createSession(owner_type, owner_guid, imported_dump_specification_formatter.get_destination_file(),
                                                                     imported_dump_specification_formatter.buildString(), True, dump_specification_formatter.buildString())
                session.set_lazy_import(lazy_import)
                session.set_logs_dir(self.getLogsDir())
                if dump_is_temporary is not None:
                    session.set_delete_dump(dump_is_temporary)
            except pmmcli_session.RSessionParamException, x:
                return None, 1000, "Could not create restore session for imported dump. Invalid parameter: " + x.get_message()
            dump_specification_formatter = imported_dump_specification_formatter
        session.set_ignore_sign(ignore_sign)

        if self.__restore_task_description.get_content_filter() is not None:
            session.set_content_filter_type(self.__restore_task_description.get_content_filter().get_content_type())
            session.set_content_filter_data(self.__restore_task_description.get_content_filter().get_content_data())

        if restore_task_description_misc is not None:
            session.set_content_restore_type(restore_task_description_misc.get_content_restore_type())

        return session, dump_specification_formatter, imported_dump_specification_formatter

    def __get_ignore_flags(self):
        ignore_sign = 0
        ignore_conflicts = None
        ignore_backup_password = False
        if self.__restore_task_description.get_ignore_errors():
            ignore_errors = self.__restore_task_description.get_ignore_errors().get_ignore_error()
            for ignore_error in ignore_errors:
                if ignore_error.get_type() == 'conflicts':
                    ignore_conflicts = 1
                if ignore_error.get_type() == 'sign':
                    ignore_sign = 1
                if ignore_error.get_type() == 'backup-password':
                    ignore_backup_password = True
        return ignore_sign, ignore_conflicts, ignore_backup_password

    def doActivity(self):
        if not self.__restore_task_description:
            self.__restore_task_description = RestoreTaskDescription.factory()
            self.__restore_task_description.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        if len(self.__parameters) > 0:
            if self.__parameters[0] == "-fixnames":
                self.__fix_names = True

        ignore_sign, ignore_conflicts, ignore_backup_password = self.__get_ignore_flags()

        selected_objects = self.__restore_task_description.get_objects().get_selected()
        conflict_resolution_rules = self.__restore_task_description.get_conflict_resolution_rules()
        restore_task_description_misc = self.__restore_task_description.get_misc()

        d_s = self.__restore_task_description.get_source().get_dump_specification()
        imported_dump_specification_formatter = None
        if d_s:
            session, dump_specification_formatter, imported_dump_specification_formatter = self.__init_rsession()
            if session is None:
                errcode = dump_specification_formatter
                errmsg = imported_dump_specification_formatter
                return session, errcode, errmsg
        else:
            dump_specification_formatter, session = self._clone_session()
            self.initLog(session, 'restore', 'deployer')

        # session is created
        self.__get_conflict_resolve_task(session, dump_specification_formatter, True)
        restore_task = self.__get_restore_task(session, dump_specification_formatter)
        """:type : pmm_task.RestoreTask"""

        try:
            if restore_task.try_get('lock-file-name') is None:
                # omit sign checker if conflicts already are resolved
                # call Conflict Detector to fix guids if in first restore session
                if not session.conflicts_resolved():
                    # now 'restore.xml' is unsigned and it is the time adjust 'base'
                    session.apply_base()

                    _logger.debug("Prepare to call backup_encrypt")
                    if self._decrypt_backup(session):
                        return None, 301, "Unable to decrypt backup. Specified key is not suitable."

                    _logger.debug("Prepare to call guids fixer")
                    session.fixGuids()

                    if self.__fix_names:
                        _logger.debug("Prepare to call names fixer")
                        session.fixNames()

                session.create_restore_specification(selected_objects)
                session.set_restore_mode("restore")

                # at this time 'restore.xml' file is created and ready to deploy
                if not ignore_conflicts:
                    _logger.debug("Prepare to detect conflicts")
                    # work with conflict detector
                    if session.detectConflicts():
                        conflicts_description = session.getConflictDescription()
                        auto_resolved_conflict = self.__isAutoResolvedConflict(conflicts_description)
                        conflicts_found = 0
                        if conflict_resolution_rules or auto_resolved_conflict:
                            conflicts_found = session.resolveConflicts(conflict_resolution_rules)
                        if not auto_resolved_conflict and (not conflict_resolution_rules or conflicts_found != 0):
                            errcode = 0
                            return Data.factory(
                                restore_task_result=RestoreTaskResult(
                                    task_id=restore_task.get_task_id(),
                                    dump_overview=session.getDumpOverview(),
                                    conflicts_description=conflicts_description)), errcode, None

                # invoke conflicts independent action
                session.fixSettings(self.__restore_task_description.get_owner_guid())

                if session.get_lazy_import() == 1:
                    restore_task.set('lock-file-name', session.get_lock_file_file(), True)
                    session_id = SessionId()
                    session_id.setValueOf_(session.get_session_id())
                    restore_task_description = RestoreTaskDescription(
                        mail_to=self.__restore_task_description.get_mail_to(),
                        owner_guid=self.__restore_task_description.get_owner_guid(),
                        owner_type=self.__restore_task_description.get_owner_type(),
                        owner_name=self.__restore_task_description.get_owner_name(),
                        top_object_id=self.__restore_task_description.get_top_object_id(),
                        top_object_type=self.__restore_task_description.get_top_object_type(),
                        top_object_name=self.__restore_task_description.get_top_object_name(),
                        top_object_guid=self.__restore_task_description.get_top_object_guid(),
                        source=RestoreTaskDescriptionSource(session_id=session_id),
                        objects=self.__restore_task_description.get_objects(),
                        ignore_errors=self.__restore_task_description.get_ignore_errors(),
                        misc=self.__restore_task_description.get_misc(),
                    )
                    cmd_pmmcli = subproc.CmdLine(pmm_config.pmmcli(),
                                                 stdin=PmmUtils.convertToXmlString(restore_task_description,
                                                                                   'restore-task-description'))
                    cmd_pmmcli.arg('--restore')
                    pmmcli_proc = cmd_pmmcli.asyncSpawn()
                    _logger.debug("Start pmmcli: %s" % cmd_pmmcli.get_cmd())

                    pmmcli_proc.poll()

                    import_task = pmm_task.ImportTask(cmd_pmmcli.get_cmd(), pmmcli_proc.get_pid(), {
                        'session_path': session.get_session_path(),
                    })
                    restore_task.set('import_task_id', import_task.get_task_id(), True)
                    _logger.debug('import_task_id=%s' % str(import_task.get_task_id()))
                    session.set_lock_file()
                    task_id_object = TaskId()
                    task_id_object.setValueOf_(restore_task.get_task_id())
                    return Data.factory(task_id=task_id_object), None, None
            else:
                imported_dump_specification_formatter, errcode, errmsg = \
                    self.__import_full_backup_from_ftp(True
                                                       , pmm_dump_formatter.DumpSpecificationFormatter(session.get_external_dump_specification_value())
                                                       , dump_specification_formatter.get_name_of_xml_file())
                restore_task = pmm_task.getPMMTaskManager().realGetTask(restore_task.get_task_id())
                import_task = pmm_task.getPMMTaskManager().realGetTask(restore_task.try_get('import_task_id'))
                """:type : pmm_task.ImportTask"""
                import_task.finish(errcode, errmsg)
                ignore_errors = [0, 111]
                if errcode not in ignore_errors:
                    return None, errcode, errmsg

            _logger.debug("Prepare check backup signature")
            if not ignore_sign:
                dump_result, check_errcode, check_message = pmm_repository_access_service.LocalRepositoryAccessService(
                    dump_specification_formatter.get_dumps_storage_credentials_formatter()).getDumpStatus(dump_specification_formatter)
                _logger.debug("Get dump status operation ended with ext code %d (%s)" % (check_errcode, check_message))
                for status in dump_result.get_dump_status():
                    if status.get_dump_status() == 'SIGN-ERROR':
                        return None, 116, "Backup has wrong signature. Restoration prohibited"

            _logger.debug("Prepare to start deployer")
            _logger.debug("CUSTOM_LOG: %s", os.environ['CUSTOM_LOG'])
            cmd_deployer = subproc.AsyncCmdLine(pmm_config.deployer())
            cmd_deployer.arg('--deploy-dump')
            if restore_task_description_misc:
                if restore_task_description_misc.get_suspend() == "true":
                    cmd_deployer.arg('--suspend')
                verbose_level = restore_task_description_misc.get_verbose_level()
                try:
                    verbose_level_value = int(verbose_level)
                    if verbose_level_value >= 1:
                        cmd_deployer.arg('--verbose')
                except (TypeError, ValueError):
                    pass

            if self.__restore_task_description.get_operability_check() == "true":
                cmd_deployer.arg('--check-operability')

            if pmmcli_config.get().force_debug_log() == 1:
                cmd_deployer.arg('--verbose')
                cmd_deployer.arg('--debug')

            cmd_deployer.arg('--session-path')
            cmd_deployer.arg(session.get_session_path())

            cmd_deployer.arg('--dump-index-file')
            cmd_deployer.arg(session.get_dump_index_file())

            # Get restore tasks list
            #
            # Need additional protection against two pmmcli instance run
            # because walking throw Task List takes some time.
            # Use osutil.Interlock instance to lock access to code section
            _logger.debug("Acquire deployer lock")
            mutex = osutil.Interlock(pmm_config.pmmcli_deployer_lock_file())
            if not mutex.lock():
                _logger.debug('Deployer lock already locked')
                return None, 201, "Unable to start restoration because there is another restoration process running"

            _logger.debug("Deployer lock acquired")
            task_manager = pmm_task.getPMMTaskManager()
            restore_task_list = task_manager.realGetTaskList(['Deploy'])
            for import_task in restore_task_list:
                task_status = import_task.get_status()
                if task_status.get_working():
                    _logger.debug("Working restore task detected (%s)" % str(import_task.get_task_id()))
                    if imported_dump_specification_formatter is not None and session.get_delete_dump():
                        self.__delete_dump(imported_dump_specification_formatter)
                    mutex.unlock()
                    return None, 201, "Unable to start restoration because there is another restoration process running"

            errcode = None
            errmsg = None
            try:
                mutex.unlock()
                _logger.debug("Deployer lock unlocked")
                _logger.debug("Start deployer: %s" % cmd_deployer.get_cmd())

                cmd_deployer.asyncSpawn()
                return_code = cmd_deployer.get_return_code()
                if return_code is not None and 0 != return_code:
                    raise Exception("Unable to start deployer '%s': %s" % (cmd_deployer.get_cmd(), cmd_deployer.get_stderr()))
                pid = cmd_deployer.pid

            except subproc.NonzeroExitException, x:
                raise PMMUtilityException('deployer', x)

            deploy_task = pmm_task.DeployTask( cmd_deployer.get_cmd(), pid, {
                'owner_guid': self.__restore_task_description.get_owner_guid(),
                'owner_type': self.__restore_task_description.get_owner_type(),
                'owner_name': self.__restore_task_description.get_owner_name(),
                'topobject_id': self.__restore_task_description.get_top_object_id(),
                'topobject_type': self.__restore_task_description.get_top_object_type(),
                'topobject_name': self.__restore_task_description.get_top_object_name(),
                'session_path': session.get_session_path(),
                'dumps_storage_credentials': dump_specification_formatter.get_dumps_storage_credentials_formatter().buildString(),
                'fullname': restore_task.get('fullname'),
                'dump_specification': dump_specification_formatter.buildString(),
                'delete_dump': str(session.get_delete_dump()),
                'name': restore_task.get('name'),
                'mailto': self.__restore_task_description.get_mail_to()})
            restore_task.set('deploy_task_id', deploy_task.get_task_id())
            pmm_task.getPMMTaskManager().updateTask(restore_task)

            # Restoration task will delete dump itself if needed.
            session.set_delete_dump(False)
        except PMMUtilityException, e:
            _logger.critical("PMMUtilityException: \n" + str(e.__class__) + " " + str(e) + "\n" +  stacktrace.stacktrace())
            errcode = 1000
            errmsg = e.subprocess.stderr
            if not errmsg:
                errmsg = str(e)
        except subproc.AsyncExecuteException, e:
            _logger.critical(u"Failure occurred while running dump deployer application. %s\n%s" % (unicode(e), stacktrace.stacktrace()))
            return None, 1001, u"Failure occurred while running dump deployer application. %s\nCommand line executed: %s" % (e.message, e.cmdline)
        except Exception, e:
            _logger.critical("Exception: \n" + str(e.__class__) + " " + str(e) + "\n" +  stacktrace.stacktrace())
            errcode = 1001
            errmsg = str(e)

        task_id_object = TaskId()
        task_id_object.setValueOf_(restore_task.get_task_id())
        data = Data.factory( task_id = task_id_object )
        if imported_dump_specification_formatter is not None and session.get_delete_dump():
            self.__delete_dump(imported_dump_specification_formatter)
        return data, errcode, errmsg

    def __isAutoResolvedConflict(self, conflicts_description):
        conflicts = conflicts_description.get_conflict()

        if len(conflicts) == 0:
            return True

        for conflict in conflicts:
            has_automatic_option = False
            conf = conflict.get_type().get_configuration()
            if conf is not None:
                resolve_opts = conf.get_resolve_options()
                if resolve_opts is not None:
                    options = resolve_opts.get_option()
                    for resolve_opt in options:
                        if resolve_opt.get_name() == 'automatic':
                            has_automatic_option = True
                            break
            if not has_automatic_option:
                return False

        return True

    def _decrypt_backup(self, session):
        """
        :param pmmcli_session.PmmcliSession session:
        :return bool:
        """
        ignore_backup_password = 0
        if self.__restore_task_description.get_ignore_errors():
            ignore_errors = self.__restore_task_description.get_ignore_errors().get_ignore_error()
            for ignore_error in ignore_errors:
                if ignore_error.get_type() == 'backup-password':
                    ignore_backup_password = 1
                    break

        dump_specification = self.__restore_task_description.get_source().get_dump_specification()
        if not dump_specification:
            dump_specification = session.get_external_dump_specification()
        if not dump_specification:
            dump_specification = session.getDumpSpecification()

        setupBackupPasswordEnv(dump_specification.get_dumps_storage_credentials())

        return session.decryptBackup(ignore_backup_password)


class MakeDumpAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__backup_task_description = None
        self.__dumps_storage_credentials_formatter = None

    def generateSessionPath(self):
        pmm_session_path = pmm_config.session_dir()
        if not os.path.isdir(pmm_session_path):
            dirutil.mkdirs(pmm_session_path, 0755)

        session_path = MakeDumpAction._generate_session_path(pmm_config.session_dir())
        try:
            while not dirutil.mkdirs(session_path, 0750, -1, plesk_config.psaadm_gid()):
                time.sleep(0.001)
                session_path = MakeDumpAction._generate_session_path(pmm_config.session_dir())
        except OSError, e:
            import errno
            if e.errno == errno.EMLINK:
                raise PMMException(e.errno, "Failed to create session directory in '%s': %s" % (pmm_config.session_dir(), e), "Clean directory '%s'" % pmm_config.session_dir())
            else:
                raise
        return session_path

    @staticmethod
    def _generate_session_path(session_dir):
        now = datetime.now()
        session_id = "%s.%03d" % (now.strftime('%Y-%m-%d-%H%M%S'), now.microsecond/1000)
        return os.path.join(session_dir, session_id)

    def validate(self):
        try:
            error_code, msg = self.get_input_validator().do(self.__parameter_stdin)
        except libxml2.parserError, ex:
            return None, 'XML parse error: ' + ex.msg + '\n' + libxml2errorHandlerErr
        if error_code:
            return None, 'Error ' + str(error_code) + ': ' + msg
        try:
            self.__backup_task_description = BackupTaskDescription.factory()
            self.__backup_task_description.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        except ExpatError:
            return None, 'ExpatError in BackupTaskDescription'
        try:
            self.__backup_task_description.get_misc().get_backup_profile_name().encode('ascii')
        except UnicodeEncodeError:
            return None, 'backup-profile-name must be ascii string'
        self.__dumps_storage_credentials_formatter = pmm_dump_formatter.DumpsStorageCredentialsFormatter(self.__backup_task_description.get_dumps_storage_credentials())
        commands_to_backup = self.convertBackupSpecification2Args(self.__backup_task_description.get_backup_specification())[0]
        if len(commands_to_backup) == 0:
            return None, 'Null-length command-to-backup in ExpatError in BackupSpecification'
        return 1, ''

    def convertBackupSpecification2Args(self, backup_specification):
        object_to_backup_list = backup_specification.get_object_to_backup()
        object_to_exclude_list = backup_specification.get_object_to_exclude()

        # list of allowed types & backup_commands sorted by priority
        allowed_types = ['server','reseller','client','domain']
        # list of the types, enumerable by ids or names
        enum_types = ['reseller','client','domain']

        commands_to_backup = []
        objects_to_backup_args = []
        objects_to_backup_warnings = []

        backup_command_index = None

        backup_all = False

        # Lists are structured as followings: [enum_types('reseller','client','domain'):fixed length] X [(name,id):fixed length] X [objects list:growing length]
        # 'backup_objects[0][1][0]' is an id of the first reseller (given by its id) and
        # 'backup_objects[1][0][2]' is a name of the third client (given by its name) if BackupSpecification has clent names and ids mixed (specification v.0.20 allow this)
        backup_objects = [[[] for col in range(2)] for row in range(len(enum_types))]
        exclude_objects = [[[] for col in range(2)] for row in range(len(enum_types))]

        backup_objects_all = [0 for row in range (len(enum_types))]
        exclude_objects_all = [0 for row in range (len(enum_types))]

        for object_item in object_to_backup_list:
            object_type = object_item.get_type()
            if object_type in allowed_types:
                if (backup_command_index ==None) or (allowed_types.index(object_type) < backup_command_index):
                    backup_command_index = allowed_types.index(object_type)
                if allowed_types.index(object_type) != 0:
                    if object_item.get_all() == 'true':
                        backup_objects_all[enum_types.index(object_type)] = 1
                    else:
                        object_id = object_item.get_id()
                        if object_id == '':
                            object_id = None
                        object_name = object_item.get_name()
                        if object_name == '':
                            object_name = None
                        if object_id:
                            backup_objects[enum_types.index(object_type)][1].append(object_id)
                        elif object_name:
                            backup_objects[enum_types.index(object_type)][0].append(object_name)
                        else:
                            objects_to_backup_warnings.append("Backup Task warning: Object to backup specified by type='%s' has neither 'id' nor 'name' specified.\n" % (object_type))

        for object_item in object_to_exclude_list:
            object_type = object_item.get_type()
            if object_type in allowed_types:
                if allowed_types.index(object_type) == 0:
                    objects_to_backup_warnings.append("Backup Task warning: 'Exclude %s' is not supported option\n" % (allowed_types[0]))
                else:
                    if object_item.get_all() == 'true':
                        exclude_objects_all[enum_types.index(object_type)] = 1
                    else:
                        object_id = object_item.get_id()
                        if object_id == '':
                            object_id = None
                        object_name = object_item.get_name()
                        if object_name == '':
                            object_name = None
                        if object_name is not None:
                            exclude_objects[enum_types.index(object_type)][0].append(object_name)
                        elif object_id is not None:
                            exclude_objects[enum_types.index(object_type)][1].append(object_id)
                        else:
                            objects_to_backup_warnings.append("Backup Task warning: Object to exclude specified by type='%s' has neither 'id' nor 'name' specified.\n" % (object_type))
        if backup_command_index is None:
            objects_to_backup_warnings.append("Backup Task warning: Could not find backup command. '%s' command will be used instead" % (allowed_types[0]))
            commands_to_backup.append(allowed_types[0])
        elif backup_command_index == 0:
            commands_to_backup.append(allowed_types[0])
        else:
            if backup_objects_all[backup_command_index-1]:
                commands_to_backup.append(allowed_types[backup_command_index]+'s-id')
                backup_all = True
                # no id args should be specifies in this case, this forces backing up all the clients/domains/etc
            else:
                if len(backup_objects[backup_command_index-1][1]) > 0:
                    commands_to_backup.append(allowed_types[backup_command_index]+'s-id')
                    for item_id in backup_objects[backup_command_index-1][1]:
                        objects_to_backup_args.append(item_id)
                    for item_name in backup_objects[backup_command_index-1][0]:
                        objects_to_backup_warnings.append("Backup Task warning: %s '%s' does not included into backup. Cannot specify %s's ids and names together.\n" % (allowed_types[backup_command_index], item_name, allowed_types[backup_command_index]))

                else:
                    commands_to_backup.append(allowed_types[backup_command_index]+'s-name')
                    for item_name in backup_objects[backup_command_index-1][0]:
                        objects_to_backup_args.append(item_name)

            rest_alls = backup_objects_all[backup_command_index:]
            for i in range(len(rest_alls)):
                if rest_alls[i]:
                    objects_to_backup_warnings.append("Backup Task warning: 'All %ss' does not included into backup being shadowed with backing up '%ss'.\n" % (enum_types[backup_command_index+i], allowed_types[backup_command_index]))

            for i in range(len(backup_objects) - backup_command_index):
                for item_name in backup_objects[i + backup_command_index][0]:
                    objects_to_backup_warnings.append("Backup Task warning: %s '%s' does not included into backup being shadowed with backing up '%ss'.\n" % (allowed_types[i+ backup_command_index], item_name, allowed_types[backup_command_index]))
                for item_id in backup_objects[i + backup_command_index][1]:
                    objects_to_backup_warnings.append("Backup Task warning: %s with id=%s does not included into backup being shadowed with backing up '%ss'.\n" % (allowed_types[i+ backup_command_index], item_id, allowed_types[backup_command_index]))

            # processing excludes, should forms one command '--exclude-XXXX' by one object type
            for i in range(len(exclude_objects_all)):
                if exclude_objects_all[i]:
                    objects_to_backup_warnings.append("Backup Task warning: 'Exclude all %ss' does not supported." % (enum_types[i]))

            for i in range(len(exclude_objects)):
                for item_id in exclude_objects[i][1]:
                    objects_to_backup_warnings.append("Backup Task warning: Exclude %s with id='%s' does not included into backup. Cannot exclude %s by its id.\n" % (enum_types[i], item_id, enum_types[i]))

        for i in range(len(exclude_objects)):
            if len(exclude_objects[i][0]) > 0:
                cmd = '--exclude-' + enum_types[i] + '='
                delimiter = ''
                for item_name in exclude_objects[i][0]:
                    cmd = cmd + delimiter + item_name
                    delimiter = ','
                commands_to_backup.append(cmd)

        warning_message = ''.join(objects_to_backup_warnings)
        return commands_to_backup, backup_all, objects_to_backup_args, warning_message

    def doActivity(self):
        self.initLog(None, 'backup', 'backup')

        if not self.__backup_task_description:
            self.__backup_task_description = BackupTaskDescription.factory()
            self.__backup_task_description.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        if not self.__dumps_storage_credentials_formatter:
            self.__dumps_storage_credentials_formatter = pmm_dump_formatter.DumpsStorageCredentialsFormatter(self.__backup_task_description.get_dumps_storage_credentials())

        session_path = self.generateSessionPath()

        # check available disk space
        disk_space_required = pmmcli_config.get().get_free_disk_space()
        local_dump_directory = plesk_config.get("DUMP_D")
        if local_dump_directory:
            if osutil.free_bytes(local_dump_directory) < (long(disk_space_required) * 1048576):
                task = pmm_task.BackupTask( '', -1, {'owner_guid':self.__backup_task_description.get_misc().get_owner_guid(),
                                                     'owner_type':self.__backup_task_description.get_misc().get_owner_type(),
                                                     'session_path':session_path,
                                                     'dumps_storage_credentials':self.__dumps_storage_credentials_formatter.buildString(),
                                                     'backup_profile_name':self.__backup_task_description.get_misc().get_backup_profile_name(),
                                                     'mailto':None,
                                                     'topobject_id': self.__backup_task_description.get_misc().get_top_object_id(),
                                                     'topobject_type': self.__backup_task_description.get_misc().get_top_object_type(),
                                                     'topobject_name': self.__backup_task_description.get_misc().get_top_object_name(),
                                                     'owner_name':self.__backup_task_description.get_misc().get_owner_name()})
                task_id_object = TaskId()
                task_id_object.setValueOf_(task.get_task_id())
                data = Data.factory( task_id = task_id_object )
                error_message = "Low Disk space for backup"
                return data, 11, error_message

        output_file = None
        use_passive_ftp_mode = None
        backup_node = None

        storage_type = self.__dumps_storage_credentials_formatter.get_storage_type()
        if storage_type in ['foreign-ftp', 'file', 'backup-node', 'extension']:
            if storage_type == 'backup-node':
                backup_node = self.__dumps_storage_credentials_formatter.getDumpStorage()
            else:
                output_file = self.__dumps_storage_credentials_formatter.getFileDumpStorage()

            if storage_type in ['foreign-ftp', 'backup-node']:
                ftp_password = self.__dumps_storage_credentials_formatter.get_password()
                os.environ['FTP_PASSWORD'] = str(ftp_password)
                if self.__dumps_storage_credentials_formatter.get_use_passive_ftp_mode() == 'true':
                    use_passive_ftp_mode = True

        commands_to_backup, backup_all, objects_to_backup_args, objects_to_backup_warning_message = self.convertBackupSpecification2Args(self.__backup_task_description.get_backup_specification())

        session_path_arg = '--session-path=' + session_path

        if len(commands_to_backup) >= 0 and commands_to_backup[0] != "server" and not backup_all and len(objects_to_backup_args) == 0:
            return None, 12, "Could not backup specified object: wrong object-to-backup format:\n" + objects_to_backup_warning_message

        backup_options = self.__backup_task_description.get_backup_specification().get_backup_options()

        extension_context_file = None
        if self.__backup_task_description.get_backup_specification().get_extension() is not None:
            extension_context_file = session_path + '/extension_context'
            with open(extension_context_file, 'w+') as f:
                f.write(self.__backup_task_description.get_backup_specification().get_extension().get_context())

        check_backup_disk_space = pmmcli_config.get().get_check_backup_disk_space()
        stderrRedirectFile = os.path.join(session_path,'.getsize.stderr')
        if check_backup_disk_space:
            cmdGetSize = subproc.CmdLine(pmm_config.backup(),
                                         stderr=open(stderrRedirectFile, 'wt'),
                                         stdin=self.__backup_task_description.get_backup_parameters())
            for backup_arg in pmm_config.plesk_backup_args():
                cmdGetSize.arg(backup_arg)

            for command in commands_to_backup:
                cmdGetSize.arg(command)
            cmdGetSize.arg('--get-size')

            if extension_context_file is not None:
                cmdGetSize.arg('--extension-id=' + str(self.__backup_task_description.get_backup_specification().get_extension().get_id()))
                cmdGetSize.arg('--extension-context-file=' + extension_context_file)

            if 'configuration-only' == backup_options.get_type():
                cmdGetSize.arg('--configuration')
            elif 'only-mail' == backup_options.get_filter():
                cmdGetSize.arg('--only-mail')
            elif 'only-phosting' == backup_options.get_filter():
                cmdGetSize.arg('--only-hosting')

            if storage_type in ['foreign-ftp', 'extension']:
                cmdGetSize.arg('--ftp')
                if output_file:
                    output_file_arg = '--output-file=' + output_file
                    cmdGetSize.arg(output_file_arg)
                if use_passive_ftp_mode:
                    cmdGetSize.arg('--ftp-passive-mode')
            if '' != self.__backup_task_description.get_backup_specification().get_backup_options().get_incremental_creation_date() \
                or 'true' == self.__backup_task_description.get_backup_specification().get_backup_options().get_incremental():
                cmdGetSize.arg('--incremental')

            if pmmcli_config.get().force_debug_log() == 1:
                cmdGetSize.arg('-vvvvv')
            cmdGetSize.arg(session_path_arg)

            # enumerate object names or ids in file
            if len(objects_to_backup_args) > 0:
                filename = os.path.join(session_path, 'from-file')
                with open(filename, 'w', ) as file:
                    os.chmod(filename, 0600)
                    for object_to_backup_arg in objects_to_backup_args:
                        if object_to_backup_arg:
                            file.write((object_to_backup_arg + "\n").encode('utf-8'))
                cmdGetSize.arg('--from-file=' + filename)

            local_backup_size = 0.
            try:
                proc = cmdGetSize.spawn()
                proc_output = proc.stdout.encode('utf-8').split(',')
                try:
                    local_backup_size = long(proc_output[0], 10) * pmmcli_config.get().get_used_space_coefficient()
                    external_backup_size = long(proc_output[1], 10) * pmmcli_config.get().get_used_space_coefficient()
                    _logger.info('Backup utility reported local backup size is %s bytes' % str(local_backup_size))
                    _logger.info('Backup utility reported external backup size is %s bytes' % str(external_backup_size))
                except ValueError:
                    _logger.warning('Unable to get backup size: Backup utility reported :\n' + proc_output)
                    pass
            except subproc.NonzeroExitException, x:
                if x.exitcode == 2:
                    originalErrorMsg = ''
                    try:
                        f = open(stderrRedirectFile, 'rb')
                        originalErrorMsg = PmmUtils.getFileLastNonEmptyLine(f)
                    except:
                        pass
                    error_message = 'Not enough free disk space to perform operation: %s. Free up disk space and' \
                                    ' run backup again.' % originalErrorMsg
                    return None, 13, error_message
                raise PMMUtilityException(cmdGetSize.get_cmd(), x)

            if local_dump_directory:
                local_free_size = osutil.free_bytes(local_dump_directory)
                _logger.info('PMMcli detected local free disk space is ' + str(local_free_size) + ' bytes')
                if (local_backup_size - local_free_size) > 0:
                    error_message = "Not enough free disk space to backup selected objects. At least %.2f MBytes" \
                                    " free disk space is required." % (local_backup_size / 1048576)
                    return None, 13, error_message

            if storage_type == 'extension':
                cmd = subproc.CmdLine(pmm_config.backup_restore_helper())
                cmd.arg("--extension-transport")
                cmd.arg(output_file)
                cmd.arg("-operation")
                cmd.arg("get-quota")
                try:
                    proc = cmd.spawn()
                    quota = json.loads(proc.stdout.encode('utf-8'))
                    if 'free' in quota:
                        external_free_size = quota['free']
                        _logger.info(
                            'PMMcli detected external free disk space is ' + str(external_free_size) + ' bytes')
                        if (external_backup_size - external_free_size) > 0:
                            error_message = "Not enough free space at the external storage to backup selected objects. At least " + str(
                                external_backup_size / 1048576) + " MBytes free disk space is required."
                            return None, 13, error_message
                except subproc.NonzeroExitException, x:
                    _logger.debug("Getting of backup storage quota failed: " + x.subprocess.stdout)

        setupBackupPasswordEnv(self.__backup_task_description.get_dumps_storage_credentials())

        if not mswindows and pmmcli_config.get().get_nice_always():
            cmdBackup = subproc.CmdLine('nice',
                                  stderr = open(os.path.join(session_path, '.stderr'), 'wt'),
                                  stdin=self.__backup_task_description.get_backup_parameters())

            nice_adjustment = pmmcli_config.get().get_nice_adjustment()
            if nice_adjustment:
                nice_adjustment_arg = '--adjustment=' + str(nice_adjustment)
                cmdBackup.arg(nice_adjustment_arg)

            cmdBackup.arg(pmm_config.backup())
        else:
            cmdBackup = subproc.CmdLine(pmm_config.backup(),
                                  stderr = open(os.path.join(session_path, '.stderr'), 'wt'),
                                  stdin=self.__backup_task_description.get_backup_parameters())

        for backup_arg in pmm_config.plesk_backup_args():
            cmdBackup.arg(backup_arg)

        for command in commands_to_backup:
            cmdBackup.arg(command)

        if mswindows:
            do_native_mssql_backup = False
            mssql_native_backup_option = self.__backup_task_description.get_backup_specification().get_backup_options().get_mssql_native_backup()
            if mssql_native_backup_option:
                do_native_mssql_backup = mssql_native_backup_option == 'true'
            else:
                do_native_mssql_backup = pmmcli_config.get().get_mssql_native_backup_enabled() == 1

            if do_native_mssql_backup:
                cmdBackup.arg('--mssql-db-content=nativeifpossible')

        owner_guid_arg = '--owner-uid=' + self.__backup_task_description.get_misc().get_owner_guid()
        cmdBackup.arg(owner_guid_arg)
        owner_type = self.__backup_task_description.get_misc().get_owner_type()
        if owner_type:
            owner_type_arg = '--owner-type=' + owner_type
            cmdBackup.arg(owner_type_arg)

        dump_rotation = self.__backup_task_description.get_backup_specification().get_backup_options().get_rotation()
        if dump_rotation:
            dump_rotation_arg = '--dump-rotation=' + dump_rotation
            cmdBackup.arg(dump_rotation_arg)

        backup_profile_id = self.__backup_task_description.get_misc().get_backup_profile_id()
        if backup_profile_id != '':
            backup_profile_id_arg = '--backup-profile-id=' + backup_profile_id
            cmdBackup.arg(backup_profile_id_arg)

        # TODO: wrap backup profile name into "" when pleskbackup will understand this
        backup_profile_name = self.__backup_task_description.get_misc().get_backup_profile_name()
        if backup_profile_name != '':
            backup_profile_name_arg = '--backup-profile-name=' + backup_profile_name
            cmdBackup.arg(backup_profile_name_arg)

        incrementalCreationDate = self.__backup_task_description.get_backup_specification().get_backup_options().get_incremental_creation_date()
        if '' != incrementalCreationDate:
            cmdBackup.arg('--incremental-creation-date=' + incrementalCreationDate)
        elif 'true' == self.__backup_task_description.get_backup_specification().get_backup_options().get_incremental():
            cmdBackup.arg('--incremental')

        split_size = self.__backup_task_description.get_backup_specification().get_backup_options().get_split_size()
        if split_size:
            split_arg = '--split=' + split_size
            cmdBackup.arg(split_arg)

        dump_description = self.__backup_task_description.get_backup_specification().get_backup_options().get_description()
        if dump_description:
            if mswindows:
                dump_description_file_name = os.path.join(session_path,'dump_description')
                dump_description_file = open(dump_description_file_name, "w")
                dump_description_file.write(dump_description.encode('utf-8'))
                dump_description_file.close()
                dump_description_file_arg = '--description-file=' + dump_description_file_name
                cmdBackup.arg(dump_description_file_arg)
            else:
                dump_description_arg = '--description=' + dump_description
                cmdBackup.arg(dump_description_arg)

        if self.__backup_task_description.get_backup_specification().get_backup_options().get_compression_level() == "do-not-compress":
            cmdBackup.arg('--no-gzip')

        if 'configuration-only' == backup_options.get_type():
            cmdBackup.arg('--configuration')
        elif 'only-mail' == backup_options.get_filter():
            cmdBackup.arg('--only-mail')
        elif 'only-phosting' == backup_options.get_filter():
            cmdBackup.arg('--only-hosting')

        if pmmcli_config.get().force_debug_log() == 1:
            cmdBackup.arg('-vvvvv')

        if self.__backup_task_description.get_backup_specification().get_backup_options().get_suspend() == "true":
            cmdBackup.arg('--suspend')

        if self.__backup_task_description.get_misc().get_owner_may_use_server_storage() == 'true':
            if pmmcli_config.get().get_keep_local_backup_if_export_to_ftp_failed() == 1:
                cmdBackup.arg('--keep-local-backup-if-export-failed')
            if self.__backup_task_description.get_misc().get_keep_local_backup() == 'true':
                cmdBackup.arg('--keep-local-backup')

        if len(self.__backup_task_description.get_backup_specification().get_file_to_exclude()) > 0:
            filename = os.path.join(session_path, 'exclude')
            with open(filename, 'w', ) as file:
                os.chmod(filename, 0600)
                for pattern in self.__backup_task_description.get_backup_specification().get_file_to_exclude():
                    if pattern:
                        file.write(base64.b64decode(pattern) + "\n")
            cmdBackup.arg('--exclude-pattern-file=' + filename)

        verbose_level = self.__backup_task_description.get_misc().get_verbose_level()
        try:
            verbose_level_value = int(verbose_level)
            if verbose_level_value >= 1:
                if verbose_level_value > 4:
                    verbose_level_value = 4
                verbose_arg = '-' + 'v'*verbose_level_value
                cmdBackup.arg(verbose_arg)
        except (TypeError, ValueError):
            pass

        cmdBackup.arg(session_path_arg)

        if output_file:
            output_file_arg = '--output-file=' + output_file
            cmdBackup.arg(output_file_arg)

        if backup_node:
            backup_node_arg= '--backup-node=' + backup_node
            cmdBackup.arg(backup_node_arg)

        if use_passive_ftp_mode:
            cmdBackup.arg('--ftp-passive-mode')

        # enumerate object names or ids in file
        if len(objects_to_backup_args) > 0:
            filename = os.path.join(session_path, 'from-file')
            with open(filename, 'w', ) as file:
                os.chmod(filename, 0600)
                for object_to_backup_arg in objects_to_backup_args:
                    if object_to_backup_arg:
                        file.write((object_to_backup_arg + "\n").encode('utf-8'))
            cmdBackup.arg('--from-file=' + filename)

        if extension_context_file is not None:
            cmdBackup.arg('--extension-id=' + str(self.__backup_task_description.get_backup_specification().get_extension().get_id()))
            cmdBackup.arg('--extension-context-file=' + extension_context_file)

        data = None
        try:
            subprocess = cmdBackup.asyncSpawn()
            pid = subprocess.get_pid()

            if mswindows:
                import win32con, win32event, win32file

                h = win32file.FindFirstChangeNotification(session_path, 1, win32con.FILE_NOTIFY_CHANGE_FILE_NAME | win32con.FILE_NOTIFY_CHANGE_SIZE | win32con.FILE_NOTIFY_CHANGE_LAST_WRITE)
                if h != win32file.INVALID_HANDLE_VALUE:
                    win32event.WaitForSingleObject(h, 1000)
                    win32file.FindCloseChangeNotification(h)

            subprocess.poll()

            task = pmm_task.BackupTask( cmdBackup.get_cmd(), pid, {'owner_guid':self.__backup_task_description.get_misc().get_owner_guid(),
                                                             'owner_type':self.__backup_task_description.get_misc().get_owner_type(),
                                                             'session_path':session_path,
                                                             'dumps_storage_credentials':self.__dumps_storage_credentials_formatter.buildString(),
                                                             'backup_profile_name':self.__backup_task_description.get_misc().get_backup_profile_name(),
                                                             'mailto':self.__backup_task_description.get_backup_specification().get_backup_options().get_mail_to(),
                                                             'topobject_id': self.__backup_task_description.get_misc().get_top_object_id(),
                                                             'topobject_type': self.__backup_task_description.get_misc().get_top_object_type(),
                                                             'topobject_name': self.__backup_task_description.get_misc().get_top_object_name(),
                                                             'owner_name':self.__backup_task_description.get_misc().get_owner_name()})
            task_id_object = TaskId()
            task_id_object.setValueOf_(task.get_task_id())
            data = Data.factory( task_id = task_id_object )
        except subproc.NonzeroExitException, x:
            e = PMMUtilityException(cmdBackup.get_cmd(), x)
            if mswindows and x.exitcode == -2146232576: # 0x80131700 - CLR_E_SHIM_RUNTIMELOAD
                e.sendNotification = False
                raise PMMException(x.exitcode, str(e), "Try to reinstall Microsoft .NET Framework 2.0 - it's required by '%s'" % pmm_config.backup())
            raise e
        return data, 0, objects_to_backup_warning_message


class DeleteDumpAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__delete_dump_query = None

    def validate(self):
        try:
            error_code, msg = self.get_input_validator().do(self.__parameter_stdin)
        except libxml2.parserError, ex:
            return None, 'XML parse error: ' + ex.msg + '\n' + libxml2errorHandlerErr
        if error_code:
            return None, 'Error ' + str(error_code) + ': ' + msg
        try:
            self.__delete_dump_query = DeleteDumpQuery.factory()
            self.__delete_dump_query.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        except ExpatError:
            return None, 'ExpatError in DeleteDumpQuery'
        return 1, ''

    def doActivity(self):
        if not self.__delete_dump_query:
            self.__delete_dump_query = DeleteDumpQuery.factory()
            self.__delete_dump_query.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        dump_specification = self.__delete_dump_query.get_dump_specification()
        dumps_storage_credentials = dump_specification.get_dumps_storage_credentials()
        object_specification = self.__delete_dump_query.get_object_specification()
        access_service = pmm_repository_access_service.create(dumps_storage_credentials)
        errcode, message = access_service.delete_dump(dump_specification, object_specification)
        return None, errcode, message


class ResolveConflictsAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__resolve_conflicts_task_description = None

    def get_resolve_conflicts_task_description(self):
        return self.__resolve_conflicts_task_description

    def validate(self):
        try:
            error_code, msg = self.get_input_validator().do(self.__parameter_stdin)
        except libxml2.parserError, ex:
            return None, 'XML parse error: ' + ex.msg + '\n' + libxml2errorHandlerErr
        if error_code:
            return None, 'Error ' + str(error_code) + ': ' + msg
        try:
            self.__resolve_conflicts_task_description = ResolveConflictsTaskDescription.factory()
            self.__resolve_conflicts_task_description.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        except ExpatError:
            return None, 'ExpatError in ResolveConflictsTaskDescription'
        return 1, ''

    def __getConflictResolveTask(self, session):
        conflict_resolve_task = None
        restore_task = self.__getRestoreTask(session)
        conflict_resolve_task_id = restore_task.get('conflict_resolve_task_id')
        if conflict_resolve_task_id is not None:
            conflict_resolve_task = pmm_task.getPMMTaskManager().realGetTask(conflict_resolve_task_id)
            conflict_resolve_task.set('session_path', session.get_session_path())
            pmm_task.getPMMTaskManager().updateTask(conflict_resolve_task)
        else:
            conflict_resolve_task = pmm_task.ConflictResolveTask({'session_path':session.get_session_path()})
            restore_task.set('conflict_resolve_task_id',conflict_resolve_task.get_task_id())
            pmm_task.getPMMTaskManager().updateTask(restore_task)
        return conflict_resolve_task

    def __getRestoreTask(self, session ):
        task_id = session.get_task_id()
        return pmm_task.getPMMTaskManager().realGetTask(task_id)

    def doActivity(self):
        if not self.__resolve_conflicts_task_description:
            self.__resolve_conflicts_task_description = ResolveConflictsTaskDescription.factory()
            self.__resolve_conflicts_task_description.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        session_id = self.get_resolve_conflicts_task_description().get_session_id()
        rsession = pmmcli_session.PmmcliSession.cloneSession(session_id)
        self.initLog(rsession, 'restore', 'conflicts')

        try:
            rsession.resolveConflictsOnce(self.get_resolve_conflicts_task_description().get_conflict_resolution_rules())
        except PMMAmbiguousObjectSpecifiedException, e:
            return None, e.getCode(), e.getDescription()
        except PMMObjectAbsentException, oae:
            return None, oae.getCode(), oae.getDescription()

        self.__getConflictResolveTask(rsession)
        dump_overview_object = rsession.getDumpOverview()
        data = Data.factory(dump_overview = dump_overview_object)
        return data, 0, None


class GetConflictsDescriptionAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameters = parameters
        self.__session_id = None

    def validate(self):
        if len(self.__parameters) != 1:
            return None, "Parameter 'session_id' not specified"
        self.__session_id = self.__parameters[0]
        return 1, ''

    def doActivity(self):
        if not self.__session_id:
            self.__session_id = self.__parameters[0]
        errcode = 0
        session = pmmcli_session.PmmcliSession(self.__session_id)
        self.initLog(session, 'restore', 'conflicts')
        overview = session.getDumpOverview()
        conflicts = session.getConflictDescription()
        result = RestoreTaskResult(dump_overview = overview, conflicts_description = conflicts)
        data = Data.factory( restore_task_result = result )
        return data, errcode, None


class GetTasksListAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__parameters = parameters
        self.__types = frozenset()
        self.__owner_guids = frozenset()
        self.__topobject_id = None
        self.__topobject_type = None

    def validate(self):
        if self.__parameter_stdin:
            try:
                error_code, msg = self.get_input_validator().do(self.__parameter_stdin)
            except libxml2.parserError, ex:
                return None, 'XML parse error: ' + ex.msg + '\n' + libxml2errorHandlerErr
            if error_code:
                return None, 'Error ' + str(error_code) + ': ' + msg
            try:
                doc = minidom.parseString(self.__parameter_stdin)
                self.__types = frozenset([node.childNodes[0].nodeValue for node in doc.getElementsByTagName('type')])
                self.__owner_guids = frozenset([node.childNodes[0].nodeValue for node in doc.getElementsByTagName('owner-guid')])
                nodes = doc.getElementsByTagName('top-object-type')
                if nodes:
                    self.__topobject_type = nodes[0].childNodes[0].nodeValue
                    nodes = doc.getElementsByTagName('top-object-id')
                    if nodes:
                        self.__topobject_id = nodes[0].childNodes[0].nodeValue
            except ExpatError:
                return None, 'ExpatError in GetTasksListAction'
        else:
            if len(self.__parameters) < 1:
                return None, "Parameter 'task_type' is not specified"
            self.__types = frozenset([t.strip() for t in self.__parameters[0].split(',')])
            if not all([t in ['Backup','Restore','Deploy', 'ConflictResolve'] for t in self.__types]):
                return None, "Task type must be 'Backup', 'Restore', 'Deploy' or 'ConflictResolve'"
            if len(self.__parameters) >= 2 and 'any' != self.__parameters[1]:
                self.__owner_guids = frozenset([self.__parameters[1]])
            if len(self.__parameters) >= 3 and 'any' != self.__parameters[2]:
                self.__topobject_id = self.__parameters[2]
            if len(self.__parameters) >= 4:
                if not self.__parameters[3] in ['server','reseller','client','domain']:
                    return None, "Top object type must be one of: 'server','reseller','client','domain'"
                self.__topobject_type = self.__parameters[3]
        return 1, ''

    def doActivity(self):
        data = Data.factory(task_list = pmm_task.getPMMTaskManager().getTaskList(self.__types,self.__owner_guids,self.__topobject_id,self.__topobject_type))
        return data, 0, None

class GetTaskAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameters = parameters
        self.__task_id = None

    def validate(self):
        if len(self.__parameters) != 1:
            return None, "Parameter 'task_id' not specified"
        self.__task_id = self.__parameters[0]
        # there is no format requirements for task-id
        return 1, ''

    def doActivity(self):
        if not self.__task_id:
            self.__task_id = self.__parameters[0]
        pmm_task_list = pmm_task.getPMMTaskManager().getTask(self.__task_id)
        if pmm_task_list:
            data = Data.factory(task_list = pmm_task_list)
            return data, 0, None
        else:
            return None, 2, 'No task found with task_id = '+str(self.__task_id)


class GetTaskStatusAction(PMMCliAction):
    def __init__(self, parameter_stdin, task_param):
        PMMCliAction.__init__(self, parameter_stdin, task_param)
        self.__parameters = task_param
        self.__task_id = None

    def validate(self):
        if len(self.__parameters) != 1:
            return None, "Parameter 'task_id' not specified"
        self.__task_id = self.__parameters[0]
        # there is no format requirements for task-id
        return 1, ''

    def doActivity(self):
        if not self.__task_id:
            self.__task_id = self.__parameters[0]
        status = pmm_task.getPMMTaskManager().getTaskStatus(self.__task_id)
        if status:
            data = Data.factory( task_status = status )
            return data, 0, None
        else:
            return None, 2, 'No task found with task_id = '+str(self.__task_id)


class GetTaskLogAction(PMMCliAction):
    def __init__(self, parameter_stdin, task_param):
        PMMCliAction.__init__(self, parameter_stdin, task_param)
        self.__parameters = task_param
        self.__task_id = None

    def validate(self):
        if len(self.__parameters) != 1:
            return None, "Parameter 'task_id' not specified"
        self.__task_id = self.__parameters[0]
        # there is no format requirements for task-id
        return 1, ''

    def doActivity(self):
        if not self.__task_id:
            self.__task_id = self.__parameters[0]
        tasklog =  pmm_task.getPMMTaskManager().getTaskLog(self.__task_id)
        if tasklog:
            data = Data.factory(task_log = tasklog)
            return data, 0, None
        else:
            return None, 2, 'No task found with task_id = '+str(self.__task_id)


class RemoveTaskDataAction(PMMCliAction):
    def __init__(self, parameter_stdin, task_param):
        PMMCliAction.__init__(self, parameter_stdin, task_param)
        self.__parameters = task_param
        self.__task_id = None

    def validate(self):
        if len(self.__parameters) != 1:
            return None, "Parameter 'task_id' not specified"
        self.__task_id = self.__parameters[0]
        # there is no format requirements for task-id
        return 1, ''

    def doActivity(self):
        if not self.__task_id:
            self.__task_id = self.__parameters[0]
        result = pmm_task.getPMMTaskManager().removeTask(self.__task_id)
        if result:
            errcode = 0
            return None, errcode, None
        else:
            return None, 2, 'No task found with task_id = '+str(self.__task_id)


class StopTaskAction(PMMCliAction):
    def __init__(self, parameter_stdin, task_param):
        PMMCliAction.__init__(self, parameter_stdin, task_param)
        self.__parameters = task_param
        self.__task_id = None

    def validate(self):
        if len(self.__parameters) != 1:
            return None, "Parameter 'task_id' not specified"
        self.__task_id = self.__parameters[0]
        # there is no format requirements for task-id
        return 1, ''

    def doActivity(self):
        if not self.__task_id:
            self.__task_id = self.__parameters[0]
        result = pmm_task.getPMMTaskManager().stopTask(self.__task_id)
        errcode = 0
        if result:
            errcode = 0
            return None, errcode, None
        else:
            return None, 2, 'No task found with task_id = '+str(self.__task_id)


class GetConfigParametersAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)

    def validate(self):
        return 1, ''

    def doActivity(self):
        config_parameters_object = ConfigParameters.factory()
        for param_name, param_value in pmmcli_config.get().iteritems():
            param = parameter.factory( name = param_name, value = param_value )
            config_parameters_object.add_parameter(param)
        data = Data.factory( config_parameters = config_parameters_object )
        return data, 0, None

class SetConfigParametersAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__config_parameters = None

    def validate(self):
        try:
            error_code, msg = self.get_input_validator().do(self.__parameter_stdin)
        except libxml2.parserError, ex:
            return None, 'XML parse error: ' + ex.msg + '\n' + libxml2errorHandlerErr
        if error_code:
            return None, 'Error ' + str(error_code) + ': ' + msg
        try:
            self.__config_parameters = ConfigParameters.factory()
            self.__config_parameters.build(minidom.parseString(self.__parameter_stdin).childNodes[0])
        except ExpatError:
            return None, 'ExpatError in ConfigParameters'
        return 1, ''

    def doActivity(self):
        if not self.__config_parameters:
            self.__config_parameters = ConfigParameters.factory()
            self.__config_parameters.build(minidom.parseString(self.__parameter_stdin).childNodes[0])

        parameters_list = self.__config_parameters.get_parameter()

        for parameter in parameters_list:
            param_name = parameter.get_name()
            param_value = parameter.get_value()
            pmmcli_config.get().writeParameter(param_name, param_value)

#        return 1, ''
        return self.__config_parameters, 0, None

class CheckFtpRepositoryAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameters = parameters
        self.__repository_specification = parameter_stdin

    def validate(self):
        return 1, ''

    def doActivity(self):
        pmm_ras = pmm_repository_access_service.FtpRepositoryAccessService(self.__repository_specification)
        errcode, message = pmm_ras.checkRepository()
        return None, errcode, message

class GetChildDumpsAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__parameter_stdin = parameter_stdin
        self.__dump_specification_formatter = None

    def validate(self):
        return 1, ''

    def doActivity(self):
        if not self.__dump_specification_formatter:
            self.__dump_specification_formatter = pmm_dump_formatter.DumpSpecificationFormatter(self.__parameter_stdin)
        name_of_xml_file = self.__dump_specification_formatter.get_name_of_xml_file()
        dump_storage_credentials_formatter = self.__dump_specification_formatter.get_dumps_storage_credentials_formatter()
        dump_storage = dump_storage_credentials_formatter.get_root_dir()

        pmm_ras = pmm_repository_access_service.LocalRepositoryAccessService(dump_storage_credentials_formatter)
        child_dumps_list, errcode, errmessage = pmm_ras._pmmras_get_child_dumps(dump_storage, name_of_xml_file)

        child_dumps_list_object = ChildDumpsList.factory()
        for child_dump in child_dumps_list:
            child_dumps_list_object.add_dump(child_dump)

        data = Data.factory( child_dumps_list = child_dumps_list_object )

        return data, errcode, errmessage


class UpgradeAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self.__procedure = None

    def validate(self):
        if 1 != len(self._params):
            return None, "Upgrade procedure name is not specified"
        procedure_name = self._params[0]
        import pmm_upgrader
        if not hasattr(pmm_upgrader, procedure_name):
            return None, "Procedure '%s' is not found" % procedure_name
        self.__procedure = getattr(pmm_upgrader, procedure_name)
        return 1, ''

    def doActivity(self):
        self.__procedure()
        return None, None, None


class PmmRasExecAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self._output_file = None

    def validate(self):
        if 0 == len(self._params):
            return None, "Output file is not specified"
        self._output_file = self._params.pop(0)
        return 1, ''

    def doActivity(self):
        env = pmm_dump_formatter.getEnvArrayFromXml(self._stdin)
        pmm_ras = pmm_repository_access_service.PmmRasExecAccessService()
        errcode, errmessage, stdout = pmm_ras._pmmras_exec(self._params, env, self._output_file)

        data_object = PlainData.factory()
        data_object.set_plain_data(stdout)

        return data_object, errcode, errmessage


class RunPleskMigratorAction(PMMCliAction):
    def __init__(self, parameter_stdin, parameters):
        PMMCliAction.__init__(self, parameter_stdin, parameters)
        self._plesk_migrator_command = None
        self._windows_username = None
        self._windows_password = None

    def validate(self):
        if mswindows:
            if len(self._params) < 3:
                return None, "Windows username, password and Plesk Migrator command is not specified"
            self._windows_username = self._params.pop(0)
            windows_password_file_name = self._params.pop(0)
            if not os.path.isfile(windows_password_file_name):
                return None, "File '%s' with windows password is not exist" % windows_password_file_name
            with open(windows_password_file_name, 'r') as windows_password_file:
                self._windows_password = windows_password_file.read()
            os.remove(windows_password_file_name)
        else:
            if len(self._params) == 0:
                return None, "Plesk Migrator command is not specified"
        return 1, ''

    def doActivity(self):
        plesk_migrator = subproc.AsyncCmdLine(pmm_config.plesk_migrator(), self._params)
        _logger.info("Run Plesk Migrator: '%s'" % plesk_migrator.get_cmd())
        try:
            if mswindows:
                plesk_migrator.set_windows_auth((self._windows_username, self._windows_password))
            plesk_migrator.asyncSpawn()
            return None, 0, ""
        except subproc.AsyncExecuteException as e:
            return None, 1, e.message


def usage():
    print("pmmcli. Plesk Migration Manager.")
    print("(c) Parallels")
    print("")
    print("Usage:")
    print("")
    print("    pmmcli <action> [<param1-value>[<param2-value>[<param3-value>[<param4-value>]]]]")
    print("")
    print("        Available action:")
    print("            --get-dumps-list")
    print("                 STDIN is 'dump-list-query' from pmm_api_xml_protocols.xsd")
    print("            --get-dump-overview")
    print("                 STDIN is 'dump-specification' from pmm_api_xml_protocols.xsd or session-id or")
    print("                 param1-value is session-id")
    print("            --check-dump")
    print("                 STDIN is 'dump-specification' from pmm_api_xml_protocols.xsd")
    print("            --export-dump-as-file")
    print("                 STDIN is 'src-dst-files-specification' from pmm_api_xml_protocols.xsd")
    print("                 optional-param1 'temp'")
    print("                 optional-param2 'include-increments'")
    print("            --export-file-as-file")
    print("                 STDIN is 'src-dst-files-specification' from pmm_api_xml_protocols.xsd")
    print("                 optional-param1 'join-volumes'")
    print("            --import-file-as-dump")
    print("                 STDIN is 'src-dst-files-specification' from pmm_api_xml_protocols.xsd")
    print("                 optional-param1 'include-increments'")
    print("            --restore")
    print("                 STDIN is 'restore-task-description' from pmm_api_xml_protocols.xsd")
    print("            --resolve-conflicts")
    print("                 STDIN is 'resolve-conflilcts-task-description' from pmm_api_xml_protocols.xsd")
    print("            --get-conflicts-description")
    print("                 param1-value is session-id")
    print("            --get-tasks-list")
    print("                 STDIN is 'tasks-list-query' from pmm_api_xml_protocols.xsd")
    print("            --get-task")
    print("                 param1-value is task-id")
    print("            --make-dump")
    print("                 STDIN is 'backup-task-description' from pmm_api_xml_protocols.xsd")
    print("            --delete-dump")
    print("                 STDIN is 'delete-dump-query' from pmm_api_xml_protocols.xsd")
    print("            --get-task-status")
    print("                 param1-value is task-id")
    print("            --get-task-log")
    print("                 param1-value is task-id")
    print("            --remove-task-data")
    print("                 param1-value is task-id")
    print("            --stop-task")
    print("                 param1-value is task-id")
    print("            --check-ftp-repository")
    print("                 Deprecated and may be dropped in future releases. Use 'pmm-ras --check-repository' instead")
    print("                 STDIN is 'dumps-storage-credentials' from pmm_api_xml_protocols.xsd")
    print("            --get-child-dumps")
    print("                 STDIN is 'dump-specification' from pmm_api_xml_protocols.xsd")
    print("            --upgrade")
    print("                 param1-value is upgrade procedure name")
    print("            --help")
    print("                 Display this help")
    print("")
    print("    The pmm_api_xml_protocols.xsd is placed at " + pmm_config.pmm_api_xml_protocols_schema())
    print("    The output is 'response' from pmm_api_xml_protocols.xsd")
    print("    The task-id and session-id is result of previous operation.")
    print("")


def bind_stdin():
    if 'PMMCLI_STDIN' in os.environ:
        sys.stdin = open(os.environ['PMMCLI_STDIN'], 'rb')


def bind_stdout():
    if 'PMMCLI_STDOUT' in os.environ:
        sys.stdout = open(os.environ['PMMCLI_STDOUT'], 'wb')


def safe_print(packet):
    try:
        sys.stdout.write(packet)
    except IOError:
        pass

def validate_path(path):
    if not os.path.isabs(path):
        return (False, "Session path must be absolute: " +  path + "\n")

    if os.path.exists(path) and not os.path.isdir(path):
        return (False, "Path " + path + " exists, but not a directory.\n")
    if not os.path.exists(path):
        try:
            dirutil.mkdirs(path, 0750)
        except UnicodeError, e:
            return (False, "Invalid character in session directory\n")
        except OSError, e:
            return (False, "Unable to create directory " + path + ": " + e.strerror + "\n")
    return (True, None)

def get_dumps_list(parameters):
    dump_list_query = sys.stdin.read()
    temp = parameters
    return ActionRunner(GetDumpsListAction, dump_list_query, temp).doActivity()

def get_config_parameters(parameters):
    return ActionRunner(GetConfigParametersAction, None, None).doActivity()

def set_config_parameters(parameters):
    config_parameters = sys.stdin.read()
    return ActionRunner(SetConfigParametersAction, config_parameters, None).doActivity()

def get_dump_overview(parameters):
    dump_specification = sys.stdin.read()
    temp = parameters
    return ActionRunner(GetDumpOverviewAction, dump_specification, temp).doActivity()

def check_dump(parameters):
    dump_specification = sys.stdin.read()
    return ActionRunner(CheckDumpAction, dump_specification, None).doActivity()

def export_dump_as_file(parameters):
    src_dst_files_specification = sys.stdin.read()
    temp = parameters
    return ActionRunner(ExportDumpAsFileAction, src_dst_files_specification, temp).doActivity()

def export_file_as_file(parameters):
    src_dst_files_specification = sys.stdin.read()
    temp = parameters
    return ActionRunner(ExportFileAsFileAction, src_dst_files_specification, temp).doActivity()

def import_file_as_dump(parameters):
    src_dst_files_specification = sys.stdin.read()
    return ActionRunner(ImportFileAsDumpAction, src_dst_files_specification, parameters).doActivity()

def restore(parameters):
    # <parameter1> is Plesk User Id which is a owner of a restore process
    # if <parameter2> is set to --force, PMM should not make conflicts detection but should just restore received Restore Specification
    restore_task_specification = sys.stdin.read()
    return ActionRunner(RestoreAction, restore_task_specification, parameters).doActivity()

def resolve_conflicts(parameters):
    resolve_conflicts_task_description = sys.stdin.read()
    return ActionRunner(ResolveConflictsAction, resolve_conflicts_task_description, None).doActivity()

def get_conflicts_description(parameters):
    # <parameter1> is session_id
    session_id = parameters
    return ActionRunner(GetConflictsDescriptionAction, None, session_id).doActivity()

def get_tasks_list(parameters):
    # Deprecated parameters, kept for capability only.
    # <parameter1> is a task type (Restore|Backup)
    # <parameter2> (optional) is a guid of the task owner or "any" string
    # <parameter3> (optional) is a id of the top object or "any" string (for 'Backup' task type, ignored for 'Restore' task type)
    # <parameter4> (optional) is a type of the top object (for 'Backup' task type, ignored for 'Restore' task type)
    deprecated_params = parameters
    if deprecated_params:
        task_params = None
    else:
        task_params = sys.stdin.read()
    return ActionRunner(GetTasksListAction, task_params, deprecated_params).doActivity()

def get_task(parameters):
    task_param = parameters
    return ActionRunner(GetTaskAction, None, task_param).doActivity()

def make_dump(parameters):
    backup_task_description = sys.stdin.read()
    return ActionRunner(MakeDumpAction, backup_task_description, None).doActivity()

def delete_dump(parameters):
    dump_description = sys.stdin.read()
    return ActionRunner(DeleteDumpAction, dump_description, None).doActivity()

def get_task_status(parameters):
    task_param = parameters
    return ActionRunner(GetTaskStatusAction, None, task_param).doActivity()

def get_task_log(parameters):
    task_param = parameters
    return ActionRunner(GetTaskLogAction, None, task_param).doActivity()

def remove_task_data(parameters):
    task_param = parameters
    return ActionRunner(RemoveTaskDataAction, None, task_param).doActivity()

def stop_task(parameters):
    task_param = parameters
    return ActionRunner(StopTaskAction, None, task_param).doActivity()

def check_ftp_repository(parameters):
    return ActionRunner(CheckFtpRepositoryAction, sys.stdin.read(), parameters).doActivity()

def get_child_dumps(parameters):
    dump_specification = sys.stdin.read()
    return ActionRunner(GetChildDumpsAction, dump_specification, None).doActivity()

def upgrade(parameters):
    return ActionRunner(UpgradeAction, None, parameters).doActivity()

def pmmras_exec(parameters):
    params = parameters
    env = sys.stdin.read()
    return ActionRunner(PmmRasExecAction, env, params).doActivity()

def run_plesk_migrator(parameters):
    return ActionRunner(RunPleskMigratorAction, None, parameters).doActivity()

def logUnhandledError(response, exception, exit_code=None):
    if exception is not None:
        ErrorReporter.sendException(exception)
    packet = PmmUtils.convertToXmlString(response, 'response')
    _logger.info("Outgoing packet:\n" + maskPassword(packet))
    safe_print(packet)
    if exit_code is not None:
        sys.exit(exit_code)


def main():
    actions = {"get-dumps-list": get_dumps_list,
               "get-dump-overview": get_dump_overview,
               "check-dump": check_dump,
               "export-dump-as-file": export_dump_as_file,
               "export-file-as-file": export_file_as_file,
               "import-file-as-dump": import_file_as_dump,
               "restore": restore,
               "resolve-conflicts": resolve_conflicts,
               "get-conflicts-description": get_conflicts_description,
               "get-tasks-list": get_tasks_list,
               "get-task": get_task,
               "make-dump": make_dump,
               "get-task-status": get_task_status,
               "get-task-log": get_task_log,
               "remove-task-data": remove_task_data,
               "stop-task": stop_task,
               "delete-dump": delete_dump,
               "get-config-parameters": get_config_parameters,
               "set-config-parameters": set_config_parameters,
               "check-ftp-repository": check_ftp_repository,
               "get-child-dumps": get_child_dumps,
               "upgrade": upgrade,
               "pmmras-exec": pmmras_exec,
               "run-plesk-migrator": run_plesk_migrator,}

    if len(sys.argv) < 2:
        usage()
        sys.exit(1)

    if sys.argv[1] == '--help':
        usage()
        sys.exit(0)

    if not sys.argv[1][2:] in actions:
        print("Unknown command \'" + sys.argv[1] + "'.\n")
        usage()
        sys.exit(1)

    PmmUtils.fixPathEnvVariable()

    pmmcli_log_dir = pmm_config.pmm_logs_directory()
    parameters = sys.argv[2:]
    validate_path(pmmcli_log_dir)
    log.initPidLog("pmmcli", pmmcli_log_dir, True)

    tempstdout = cStringIO.StringIO()
    tempstderr = cStringIO.StringIO()

    try:
        try:
            sys.stdout = tempstdout
            sys.stderr = tempstderr
            bind_stdin()
            try:
                data_action_response, errcode_response, error_message = actions.get(sys.argv[1][2:])(parameters)
                if not errcode_response:
                    errcode_response = 0
            except PmmcliActionParamException, ex:
                errcode_response = 1
                data_action_response = None
                error_message = "Invalid input parameters in '" + sys.argv[1] + "' command:\n" + ex.get_message()
            if not error_message:
                error_message = ""
        finally:
            sys.stdout = sys.__stdout__
            sys.stderr = sys.__stderr__
            bind_stdout()
        response = Response( errcode = errcode_response, data = data_action_response, errmsg = error_message)
        packet = PmmUtils.convertToXmlString(response,'response')
        _logger.info("Outgoing packet:\n" + maskPassword(packet))
        safe_print(packet)
    except pmmcli_session.RSessionException, e:
        _logger.critical("RSessionException: \n" + str(e) + "\n" + stacktrace.stacktrace())
        response = Response(errcode=3, errmsg="Session with id='" + str(e.get_session_id()) + "' not found")
        logUnhandledError(response, e)
    except (pmm_dump_formatter.DumpsStorageCredentialsFormatException, pmm_dump_formatter.DumpSpecificationFormatException), e:
        response = Response(errcode=1, errmsg="Wrong format for dump specification: " + e.get_message()+ "\n" + stacktrace.stacktrace())
        logUnhandledError(response, e, 1)
    except PMMUtilityException, e:
        _logger.critical("PMMUtility exception: \n" + str(unicode(e)) + "\n" + stacktrace.stacktrace())
        response = Response(errcode=1000, errmsg="pmm utility '" + e.utility() + "' raised an exception. Error code is: " + str(e.exitcode) + "\n" + "See pmmcli.log to find out detailed information on this")
        logUnhandledError(response, e, 1)
    except PMMException, e:
        _logger.critical("PMMException: \n" + str(e) + "\n" + stacktrace.stacktrace())
        oe = cStringIO.StringIO()
        writeExecutionResult(EncoderFile(oe, "utf-8"), e)
        response = Response(errcode=1004, errmsg=oe.getvalue())
        logUnhandledError(response, e, 1)
    except osutil.PsException, e:
        _logger.critical("PsException in pmmcli: \n" + str(e.__class__) + " " + str(e) + "\n" + stacktrace.stacktrace())
        response = Response(errcode=1003, errmsg="Exec format error in ps utility: command '" + str(e) + "' was failed")
        logUnhandledError(response, e, 1)
    except Exception, e:
        _logger.critical("Runtime error in pmmcli: \n" + str(e.__class__) + " " + str(e) + "\n" + stacktrace.stacktrace())
        response = Response(errcode=1001, errmsg=str(e))
        logUnhandledError(response, e, 1)
    except:
        _logger.critical("Unhandled exception in pmmcli: \n" +  stacktrace.stacktrace())
        response = Response(errcode=1002, errmsg="Unhandled exception in pmmcli: \n" + stacktrace.stacktrace())
        logUnhandledError(response, None, 1)

if __name__ == '__main__':
    main()

