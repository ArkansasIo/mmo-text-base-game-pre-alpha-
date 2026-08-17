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
print('created', *sorted(p.name for p in ROOT.glob('ui_*.wav')))
