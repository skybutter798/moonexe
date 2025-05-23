<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function faq()
    {
        $faqs = [
            [
                'q' => 'Is MoonExe available in my country?',
                'a' => 'Yes. MoonExe supports users in over 180 countries. You can register and use the platform as long as your region is not under restricted jurisdiction.',
            ],
            [
                'q' => 'How much can I earn daily as an exchange partner?',
                'a' => '<ul class="list-disc ml-5 mt-1">
                            <li>The currency pair (e.g. 0.4%–1.2% spread profit);</li>
                            <li>Daily exchange limits for each pair;</li>
                            <li>Your total liquidity (USDT provided);</li>
                            <li>Market demand on the day.</li>
                        </ul>',
            ],
            [
                'q' => 'What is the minimum USDT required to start?',
                'a' => 'You can start with as low as 100 USDT, but larger amounts may yield higher profits due to spread scaling.',
            ],
            [
                'q' => 'Can I withdraw profits anytime?',
                'a' => 'Yes. Once a transaction is complete and profits are generated, you can withdraw or reinvest anytime.',
            ],
            [
                'q' => 'How does MoonExe calculate daily spread profits?',
                'a' => 'Each day, the platform sets dynamic buy/sell rates based on global liquidity. Profit = (Sell rate - Buy rate) × traded amount.',
            ],
            [
                'q' => 'What happens when the daily quota of a currency pair is full?',
                'a' => 'You will need to choose another available currency pair with remaining volume. All quotas reset daily.',
            ],
            [
                'q' => 'Are there any risks in joining as a liquidity provider?',
                'a' => 'The model is low-risk but not risk-free. Market fluctuations or pairing saturation can affect profit availability.',
            ],
            [
                'q' => 'Can I refer friends or partners and earn rewards?',
                'a' => 'Yes! MoonExe supports referral-based commissions and long-term profit sharing for partners and community builders.',
            ],
            [
                'q' => 'Is MoonExe a wallet or an exchange?',
                'a' => 'MoonExe is a hybrid ExFi platform — combining features of centralized wallets and decentralized profit-sharing through exchange activity.',
            ],
            [
                'q' => 'Does MoonExe support fiat bank withdrawals?',
                'a' => 'Currently, the platform supports crypto in/out (mainly USDT). Fiat withdrawals may be supported via partner gateways in the future.',
            ],
            [
                'q' => 'Is there a mobile app for MoonExe?',
                'a' => 'A mobile app is under development. For now, users can access the platform via mobile-friendly web interface.',
            ],
            [
                'q' => 'If I refer someone and they deposit 1,000 USDT, how much do I earn?',
                'a' => 'You will receive 5% of it (Intermediate Level). ✅ You get: 5% × 1,000 = 50 USDT',
            ],
            [
                'q' => 'Do I earn from my referral’s daily exchange activity?',
                'a' => 'Yes. You get 10% of their daily spread profit. ✅ Example: If they earn 100 USDT, you get 10 USDT.',
            ],
            [
                'q' => 'What’s the difference between Referral Rewards and Matching Rewards?',
                'a' => '<b>Referral Rewards:</b> one-time bonuses from deposits.<br><b>Matching Rewards:</b> recurring, from their daily profits.',
            ],
            [
                'q' => 'Can I earn from team referrals, not just my direct ones?',
                'a' => 'Yes. Example: Team volume 1,000,000 USDT = 12% upgrade bonus + 40% spread matching from your team.',
            ],
            [
                'q' => 'How can I increase my profit share percentage?',
                'a' => '<ul class="list-disc ml-5">
                            <li>Entry (100–990): 40%</li>
                            <li>Intermediate (1,000–9,990): 45%</li>
                            <li>Advanced (10,000–99,990): 50%</li>
                            <li>VIP (100,000+): 60%</li>
                        </ul>',
            ],
            [
                'q' => 'How much can I earn monthly as a partner?',
                'a' => '<ul class="list-disc ml-5">
                            <li>Entry: 3%–5%</li>
                            <li>Intermediate: 4%–6%</li>
                            <li>Advanced: 5%–8%</li>
                            <li>VIP: 6%–10%</li>
                        </ul>',
            ],
        ];

        return view('user.faq', [
            'title' => 'FAQ',
            'faqs' => $faqs
        ]);

    }
}
