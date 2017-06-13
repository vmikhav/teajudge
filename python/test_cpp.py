from __future__ import print_function
from platform import system, machine
import sys 
import json
from sandbox import * 

def eprint(*args, **kwargs):
	print(*args, file=sys.stderr, **kwargs)

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
		libPos = path.find(b"/lib/x86_64-linux-gnu/")
		if path == b"/etc/ld.so.cache" or libPos == 0 or libPos == 4: # and mode == O_RDONLY:
			return SandboxAction(S_ACTION_CONT)
		#eprint(path)
		return SandboxAction(S_ACTION_KILL, S_RESULT_RF)
	pass

result_name = dict((getattr(Sandbox, 'S_RESULT_%s' % i), i) for i in \
	('PD', 'OK', 'RF', 'RT', 'TL', 'ML', 'OL', 'AT', 'IE', 'BP'))

s = Sandbox(sys.argv[1], quota=dict(wallclock=int(sys.argv[2])+1500, cpu=int(sys.argv[2]), memory=2**int(sys.argv[3]), disk=1048576)) 
s.quota 
s.policy = SelectiveOpenPolicy(s)
s.run() 
rdict = s.probe()
rdict['result'] = result_name.get(s.result, 'NA')
eprint(json.dumps(rdict))