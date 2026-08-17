Round-resolution retest, 2026-08-17:

Battle #2 resolved Round 1 through the browser. The console displayed a persisted round report, symmetrical damage score 231 / 231, one loss on each side, and power reserve reduced from 5,000 to 4,957. Initiative/action processing and report persistence are functioning. One presentation/state issue was found: hull values could become negative after lethal damage (shown as -43/90) even though quantity and destroyed status were updated. The resolver is being corrected to clamp hull values to zero before saving/rendering.
