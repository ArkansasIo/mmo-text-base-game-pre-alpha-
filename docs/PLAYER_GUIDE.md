# Player Guide

## Command objective

Build a durable civilization, expand through the seeded universe, secure power and resources, coordinate with guild members, and win conflicts through superior preparation rather than a single attack. Every system is connected: infrastructure produces capability, power keeps systems online, intelligence informs targets, and fleets convert preparation into strategic control.

## First session

Create an account from the title page, choose a home-world name and race, then sign in through **Civilization Login**. Begin with infrastructure and power. The command shell exposes Quick Access links for the major systems, while the left navigation organizes Empire, Military, Operations, Intelligence, and Diplomacy tools.

## World development

Worlds use a deterministic A–Z taxonomy with planet and moon classes, biomes, sub-biomes, and size values from 1 to 9. Infrastructure upgrades improve production, storage, command capacity, defense, and specialized facilities. The server validates upgrades and applies the progression policy; changing a browser field does not bypass a cap.

## Power and economy

The power grid models production, consumption, storage, and transfer across planets, moons, starbases, moon bases, and space stations. Keep reserve capacity above the demand of active infrastructure and military systems. Administrators can tune global multipliers, but players should treat power as a strategic resource rather than an unlimited utility.

## Fleet and combat

The RTS Combat Engine supports orbital, planetary, moon, station, and frontier theaters. Fleets use hull, shields, armor, attack, defense, range, speed, initiative, morale, ammunition, position, and energy draw. Orders include standard attack, bombardment, intercept, flank, siege, blockade, focus fire, jamming, advance, guard, shield wall, escort, reinforce, repair, countermeasure, and withdrawal.

Battles resolve in persistent rounds. Initiative determines action order, range and accuracy influence damage, shields absorb incoming energy, armor mitigates hull damage, guard reduces exposure, morale affects readiness, and power is consumed by the engagement. Use repair and reinforcement actions before the force becomes unsustainable.

## Attack and defense waves

Missions can be assault, defense, raid, intercept, siege, escort, or expedition campaigns. A mission records its theater, objective, current phase, active wave, wave limit, target, events, reports, salvage, and reward. Clearing a wave can spawn the next reinforcement wave. The current hard maximum is eight waves.

## Sabotage

Sabotage Operations provide covert actions against opposing players. Power blackout, weapons disruption, shield infiltration, production strike, logistics cut, defense breach, and command intrusion use infiltration power against counterintelligence. Successful actions create temporary effects; detected actions raise the target alert and trace profile. Use countermeasures to purge active effects.

## Communications and guilds

Use Communications for private messages and guild channels. Private messages require a valid opposing player UID and are protected by authenticated sessions, escaped rendering, and CSRF validation. Guild channels require membership through the legacy `users.allyid` field. The default command and general channels are created when a guild member opens the console.

## Progression caps

The current recommended caps are infrastructure 30, core research 30, Stargate and hyperspace 25, power 25, combat technology 30, combat sites 25, combat installations 20, military rank 50, unit veterancy 10, and battle waves 8. See the [balance proposal](BALANCE_PATCH_CAPS_PROPOSAL.md) for rollout and compatibility details.

## Good operational habits

Keep power reserve available before launching a mission, use reconnaissance before committing a wave, assign defensive orders to damaged units, protect logistics, and communicate battle plans through guild channels. Treat detected sabotage as an intelligence event: raising counterintelligence can be as valuable as adding another weapon system.

## Population and army readiness

Every new commander receives **2,500,000 untrained units** in the reserve. Training converts reserve population into attack, defense, covert, and anti-covert corps. The combined trained army begins with a **250,000-unit base capacity**; once that capacity is full, expand the applicable army-capacity systems before recruiting more trained units.

Planet and moon populations are generated independently within bounded ranges, so each world has a distinct demographic profile. Higher-habitability and larger worlds generally provide stronger population potential. Keep food, water, and power reserves stable because population and military sustainment are linked to the wider resource economy.

## Guild operations

A guild can contain up to **150 commanders**. Found a guild from the Guild Command console or accept an invitation from an officer. Guild contributions increase the shared treasury and strengthen production, defense, research, and fleet-recovery bonuses. Officers should keep the roster active, use guild channels for coordination, and avoid exhausting the treasury on a single campaign.
