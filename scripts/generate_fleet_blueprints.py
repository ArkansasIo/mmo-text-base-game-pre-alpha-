from pathlib import Path

classes = [
    ('A','Assault','direct damage and breakthrough'),('B','Bastion','armor, shields, and holding actions'),('C','Courier','speed, cargo, and rapid logistics'),('D','Dreadnought','capital weapons and heavy armor'),
    ('E','Expedition','exploration, scanning, and endurance'),('F','Frigate','balanced escort and patrol operations'),('G','Gunship','high-output weapons and interception'),('H','Hauler','bulk cargo and resource transport'),
    ('I','Interceptor','speed, evasion, and anti-fighter work'),('J','Jammer','electronic warfare and signature control'),('K','Carrier','fleet projection and drone operations'),('L','Logistics','repair, refit, and fleet support'),
    ('M','Miner','extraction, refinery, and industrial support'),('N','Nexus','command coordination and network bonuses'),('O','Orbital','station defense and long-range fire'),('P','Patrol','low-cost security and response'),
    ('Q','Quantum','advanced warp and precision systems'),('R','Recon','intelligence, sensors, and stealth'),('S','Siege','planetary bombardment and shield breaking'),('T','Tactical','adaptive combat and fleet control'),
    ('U','Utility','multi-role engineering and recovery'),('V','Vanguard','elite assault and command presence'),('W','Warbarge','sustained heavy warfare'),('X','Xenotech','rare technology and specialist systems'),
    ('Y','Yacht','executive transport and diplomatic mobility'),('Z','Zenith','endgame strategic superiority')
]
roles = ['scout','escort','line','command']
custom = {('A',0):('scout','Scout Corvette'),('B',0):('frigate','Frontier Frigate'),('D',0):('destroyer','Siege Destroyer'),('K',0):('carrier','Fleet Carrier')}
entries = []
for ci,(code,cname,focus) in enumerate(classes):
    count = 4 if ci < 12 else 3
    for variant in range(count):
        key, display = custom.get((code,variant),(f'{code.lower()}_{roles[variant]}_{ci+1:02d}', f'{cname} {roles[variant].title()}'))
        tier = 1 + ((ci + variant) // 4)
        scale = 1 + ci * 0.24 + variant * 0.18
        attack = round(8 * scale * (1.45 if variant == 2 else 1.0) * (1.15 if code in 'AGSVWZ' else 1.0))
        defense = round(6 * scale * (1.5 if variant == 1 else 1.0) * (1.18 if code in 'B D K L O W Z'.replace(' ','') else 1.0))
        hull = round(90 * scale * (1.65 if code in 'BDOW' else 1.0))
        shield = round(35 * scale * (1.7 if code in 'BJKQXZ' else 1.0))
        speed = round(90 * (1.2 if variant == 0 else 1.0) * (1.35 if code in 'CIQR' else 0.86 if code in 'DHW' else 1.0))
        cargo = round(25 * scale * (3.2 if code in 'CHMUY' else 1.0) * (1.6 if variant == 0 else 1.0))
        sensor = round(18 * scale * (2.4 if code in 'EJNRQX' else 1.0))
        power = round(40 * scale * (1.25 if code in 'DKNOWZ' else 1.0))
        crew = round(4 * scale * (1.35 if code in 'BDKWZ' else 1.0))
        armor = round(8 * scale * (1.5 if code in 'BDSVW' else 1.0))
        capacitor = round(10 * scale * (1.5 if code in 'JKNQX' else 1.0))
        signature = round(1.0 + (0.8 if code in 'BDHOW' else 0.2 if code in 'CIJQRX' else 0.5) + variant*0.08,2)
        warp = round(1.0 + (0.8 if code in 'C I Q R Y'.replace(' ','') else 0.25),2)
        evasion = round(4 + (8 if code in 'CIQR' else 2 if code in 'AEJPXY' else 0) + variant,1)
        drone = round(2 + (12 if code in 'KNUXZ' else 3 if code in 'ELT' else 0) + variant*2)
        salvage = round(3 + (15 if code in 'ELMU' else 5 if code in 'HXY' else 0) + variant,1)
        metal = round(1200 * scale * (1.4 if code in 'BDKOWZ' else 1.0))
        crystal = round(600 * scale * (1.5 if code in 'GIJQX' else 1.0))
        energy = round(100 * scale * (1.5 if code in 'JKNOQXZ' else 1.0))
        build = round(5 * scale * (1 + variant*0.65))
        high_slots = max(1, min(8, 1 + tier // 2 + (1 if variant == 2 else 0) + (1 if code in 'AGSVWZ' else 0)))
        medium_slots = max(1, min(8, 1 + tier // 3 + (1 if variant in (0, 3) else 0) + (1 if code in 'EJKNQRX' else 0)))
        low_slots = max(1, min(8, 1 + tier // 3 + (1 if variant in (1, 3) else 0) + (1 if code in 'BHLMUY' else 0)))
        details = f'{display} is a {cname.lower()}-class hull configured for {focus}. Its primary profile emphasizes {"attack" if variant==2 else "survivability" if variant==1 else "mobility" if variant==0 else "command flexibility"}, while secondary systems provide a distinct fitting choice for fleet doctrine.'
        entries.append(dict(key=key,name=display,class_code=code,class_name=cname,role=roles[variant],tier=tier,description=details,attack=attack,defense=defense,capacity=cargo,high_slots=high_slots,medium_slots=medium_slots,low_slots=low_slots,hull=hull,shield=shield,speed=speed,cargo=cargo,sensor=sensor,power_grid=power,crew=crew,armor=armor,capacitor=capacitor,signature=signature,warp=warp,evasion=evasion,drone_bandwidth=drone,salvage=salvage,metal=metal,crystal=crystal,energy=energy,build_minutes=build))
assert len(entries) == 90

def php(v):
    if isinstance(v,str): return "'" + v.replace("'","\\'") + "'"
    if isinstance(v,float): return f'{v:.2f}'
    return str(v)

lines = ['<?php', '', 'final class FleetBlueprintCatalog', '{', '    public const BLUEPRINTS = [']
for e in entries:
    lines.append('        ' + php(e['key']) + ' => [' + ', '.join(f"'{k}'=>{php(v)}" for k,v in e.items() if k != 'key') + '],')
lines += ['    ];', '    public static function all(): array { return self::BLUEPRINTS; }', '}', '?>', '']
Path('/home/ubuntu/stargatewars-clone-1.5/base/FleetBlueprintCatalog.class.php').write_text('\n'.join(lines))
print(f'generated {len(entries)} fleet blueprints')
