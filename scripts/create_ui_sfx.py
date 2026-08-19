import math, wave
from pathlib import Path

ROOT=Path('/home/ubuntu/stargatewars-clone-1.5/audio')
ROOT.mkdir(parents=True, exist_ok=True)
RATE=44100

def write(name, duration, fn):
    n=int(RATE*duration)
    frames=bytearray()
    for i in range(n):
        t=i/RATE
        sample=max(-1,min(1,fn(t,duration)))
        frames += int(sample*32767).to_bytes(2,'little',signed=True)
    with wave.open(str(ROOT/name),'wb') as w:
        w.setnchannels(1); w.setsampwidth(2); w.setframerate(RATE); w.writeframes(frames)

def env(t,d):
    return max(0.0, 1-t/d)**2

def tone(freq,amp=0.25):
    return lambda t,d: amp*env(t,d)*math.sin(2*math.pi*freq*t)

def chirp(a,b,amp=0.22):
    return lambda t,d: amp*env(t,d)*math.sin(2*math.pi*(a*t+(b-a)*t*t/(2*d)))

def double():
    def f(t,d):
        return 0.22*env(t,d)*(math.sin(2*math.pi*660*t)+0.55*math.sin(2*math.pi*990*t))
    return f

def confirm():
    def f(t,d):
        return 0.2*env(t,d)*(math.sin(2*math.pi*523*t)+0.7*math.sin(2*math.pi*784*t))
    return f

write('ui_hover.wav',0.09,chirp(720,1080,0.12))
write('ui_click.wav',0.12,tone(420,0.18))
write('ui_confirm.wav',0.34,confirm())
write('ui_warning.wav',0.28,double())
def pulse(freq, amp=0.2, cycles=3):
    def f(t,d):
        gate=0.5+0.5*math.sin(2*math.pi*cycles*t/d)
        return amp*env(t,d)*gate*math.sin(2*math.pi*freq*t)
    return f
def rising_confirm():
    def f(t,d):
        return 0.2*env(t,d)*(math.sin(2*math.pi*(420*t+420*t*t/d))+0.5*math.sin(2*math.pi*(840*t+280*t*t/d)))
    return f
def impact():
    def f(t,d):
        noise=math.sin(2*math.pi*73*t)+0.4*math.sin(2*math.pi*131*t)
        return env(t,d)*(0.22*noise+0.08*math.sin(2*math.pi*330*t))
    return f
write('mission_dispatch.wav',0.24,rising_confirm())
write('mission_success.wav',0.48,confirm())
write('combat_alert.wav',0.38,impact())
write('research_complete.wav',0.62,chirp(360,1440,0.2))
write('market_trade.wav',0.28,double())
write('notification_ping.wav',0.18,tone(880,0.16))
print('created', *sorted(p.name for p in ROOT.glob('*.wav')))
