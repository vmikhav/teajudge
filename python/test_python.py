from __future__ import print_function
from platform import system, machine
from posix import O_RDONLY
import os
import sys 
import json
from sandbox import * 

def eprint(*args, **kwargs):
	print(*args, file=sys.stderr, **kwargs)
'''
class SelectiveOpenPolicy(SandboxPolicy):
	SC_open   = (2, 0)  if machine() == 'x86_64' else (5, 0)
	SC_unlink = (87, 0) if machine() == 'x86_64' else (10, 0)
	O_CLOEXEC = 0O2000000
	READABLE_FILE_PATHS = []  # Default readable file paths

	WRITEABLE_FILE_PATHS = []

	sc_table = None
	sc_safe = dict( # white list of essential linux syscalls
		i686 = set([3, 4, 19, 45, 54, 90, 91, 122, 125, 140, 163, \
					192, 197, 224, 243, 252, ]),
		x86_64 = set([0, 1, 2, 5, 8, 9, 10, 11, 12, 16, 25, 63, 158, 231, ])
	)
	sc_safe['x86_64'] = sc_safe['x86_64'] | set([
		# User-defined safe calls added here
		3,      # close
		4,      # stat
		6,      # lstat
		13,     # rt_sigaction
		14,     # rt_sigprocmask
		15,     # rt_sigreturn
		21,     # access
		#22,     # pipe MATLAB
		32,     # dup
		33,     # dup3
		39,     # getpid MATLAB
		#41,     # sendfile MATLAB **CONSIDER**
		#42,     # socket MATLAB **CONSIDER**
		#43,     # connect MATLAB **CONSIDER**
		#56,     # clone MATLAB THIS IS A NO-NO. Breaks sandbox.
		#59,     # execve MATLAB ** CONSIDER **
		#61,     # wait4 MATLAB
		72,     # fcntl
		78,     # getdents
		79,     # getcwd
		#80,     # chdir MATLAB **CONSIDER**
		89,     # readlink
		97,     # getrlimit
		100,    # times
		102,    # getuid
		104,    # getgid
		107,    # geteuid
		108,    # getegid
		#110,    # getppid MATLAB
		#111,    # getpgrp MATLAB
		202,    # futex
		#203,    # sched_setaffinity MATLAB
		#204,    # sched_getaffinity MATLAB
		218,    # set_tid_address
		#257,    # openat MATLAB ***CONSIDER***
		#269,    # faccessat MATLAB
		273,    # set_robust_list
	])

	def __init__(self, sbox, extraPaths = [], extraWriteablePaths = []):
		assert(isinstance(sbox, Sandbox))
		self.READABLE_FILE_PATHS += extraPaths
		self.WRITEABLE_FILE_PATHS += extraWriteablePaths

		# initialize table of system call rules
		self.sc_table = [self._KILL_RF, ] * 1024
		for scno in self.sc_safe[os.uname()[4]]:
			self.sc_table[scno] = self._CONT
		self.sbox = sbox
		self.error = 'UNKNOWN ERROR. PLEASE REPORT'

	def __call__(self, e, a):
		ext = e.ext0 if arch() == 'x86_64' else 0
		if e.type == S_EVENT_SYSCALL and (e.data, ext) == self.SC_open:
			return self.SYS_open(e, a)

		elif e.type == S_EVENT_SYSCALL and (e.data, ext) == self.SC_unlink:
			return self.SYS_unlink(e, a)

		elif e.type == S_EVENT_SYSRET and (e.data, ext) == self.SC_unlink:
			return self._CONT(e, a)  # allow return from unlink

		elif e.type in (S_EVENT_SYSCALL, S_EVENT_SYSRET):
			if machine == 'x86_64' and e.ext0 != 0:
				return self._KILL_RF(e, a)
			elif (e.data, ext) == self.SC_unlink and e.type == S_EVENT_SYSCALL:
				return self.SYS_unlink(e, a)
			else:
				return self.sc_table[e.data](e, a)

		else:
			# bypass other events to base class
			return SandboxPolicy.__call__(self, e, a)

	def _CONT(self, e, a): # continue
		a.type = S_ACTION_CONT
		return a

	def _KILL_RF(self, e, a): # restricted func.
		self.error = "ILLEGAL SYSTEM CALL (#{0})".format(e.data)
		a.type, a.data = S_ACTION_KILL, S_RESULT_RF
		return a

	def SYS_open(self, e, a):
		pathBytes, mode = self.sbox.dump(T_STRING, e.ext1), e.ext2
		path = pathBytes.decode('utf8').strip()
		path = collapseDotDots(path)

		if '..' in path:
			# Kill any attempt to work up the file tree
			self.error = "ILLEGAL FILE ACCESS ({0},{1})".format(path, mode)
			return SandboxAction(S_ACTION_KILL, S_RESULT_RF)
		elif not path.startswith('/'):
			# Allow all access to the current directory (which is a special directory in /tmp)
			return SandboxAction(S_ACTION_CONT)
		else:
			for prefix in self.READABLE_FILE_PATHS + self.WRITEABLE_FILE_PATHS:
				if path.startswith(prefix):
					if (prefix in self.WRITEABLE_FILE_PATHS or
								mode == O_RDONLY or
								mode == O_RDONLY|self.O_CLOEXEC):
						return SandboxAction(S_ACTION_CONT)
			self.error = "ILLEGAL FILE ACCESS ({0},{1})".format(path, mode)
			return SandboxAction(S_ACTION_KILL, S_RESULT_RF)

	def SYS_unlink(self, e, a):
		pathBytes = self.sbox.dump(T_STRING, e.ext1)
		path = pathBytes.decode('utf8')
		if path.startswith('/tmp/'):
			return self._CONT(e, a)
		else:
			self.error = "Attempt to unlink {0}".format(path)
			return SandboxAction(S_ACTION_KILL, S_RESULT_RF)
	pass
'''
class SelectiveOpenPolicy(SandboxPolicy):
	SC_open = ((2, 0), (5, 1)) if machine() == 'x86_64' else (5, )
	sc_safe = dict( # white list of essential linux syscalls
		i686 = set([3, 4, 19, 45, 54, 90, 91, 122, 125, 140, 163, \
					192, 197, 224, 243, 252, ]),
		x86_64 = set([0, 1, 2, 5, 8, 9, 10, 11, 12, 16, 25, 63, 158, 231, ])
	)
	def __init__(self, sbox):
		assert(isinstance(sbox, Sandbox))
		self.sbox = sbox
		self.sc_table = [self._KILL_RF, ] * 1024
		for scno in self.sc_safe[machine()]:
			self.sc_table[scno] = self._CONT
	def __call__(self, e, a):
		if e.type == S_EVENT_SYSCALL:
			sc = (e.data, e.ext0) if machine() == 'x86_64' else e.data
			if sc in self.SC_open:
				return self.SYS_open(e, a)
		return super(SelectiveOpenPolicy, self).__call__(e, a)
	def _CONT(self, e, a): # continue
		a.type = S_ACTION_CONT
		return a
	def _KILL_RF(self, e, a): # restricted func.
		a.type, a.data = S_ACTION_KILL, S_RESULT_RF
		return a
	def SYS_open(self, e, a):
		path, mode = self.sbox.dump(T_STRING, e.ext1), e.ext2
		if path is None:  # e.ext1 is an invalid address
			return SandboxAction(S_ACTION_KILL, S_RESULT_RT)
		libPos = path.find(b"/lib/")
		if mode == O_RDONLY or path == b"/etc/ld.so.cache" or libPos == 0 or libPos == 4 or path.find(b"/proc/meminfo") == 0 or path.find(b"/dev/urandom") == 0 or path.find(b"/etc/") == 0:
			return SandboxAction(S_ACTION_CONT)
		#if mode != O_RDONLY:
			#print(path)
		#if path == b"./test3.py" and mode == O_RDONLY:
		#return SandboxAction(S_ACTION_CONT)
		return SandboxAction(S_ACTION_KILL, S_RESULT_RF)
	pass

result_name = dict((getattr(Sandbox, 'S_RESULT_%s' % i), i) for i in \
	('PD', 'OK', 'RF', 'RT', 'TL', 'ML', 'OL', 'AT', 'IE', 'BP'))

if __name__ == '__main__':
	s = Sandbox(["/usr/bin/python3", sys.argv[1]], quota=dict(wallclock=int(sys.argv[2])+1500, cpu=int(sys.argv[2]), memory=2**int(sys.argv[3]), disk=1048576)) 
	s.quota 
	s.policy = SelectiveOpenPolicy(s)
	s.run() 
	rdict = s.probe()
	rdict['result'] = result_name.get(s.result, 'NA')
	eprint(json.dumps(rdict))