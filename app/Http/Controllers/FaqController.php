<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function faq()
    {
        $faqs = [
            // ===== Existing FAQs =====
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

            // ===== New: Account & Registration =====
            [
                'q' => 'Who can open a MoonExe account?',
                'a' => 'MoonExe is open to users globally. All nationalities are welcome.',
            ],
            [
                'q' => 'Can I change my registered email address?',
                'a' => 'To ensure the highest level of account security, changing your registered email address is not permitted. Your email is part of your identity verification. If you have issues accessing your email, please contact support.',
            ],

            // ===== New: Deposits & Withdrawals =====
            [
                'q' => 'What is the minimum deposit amount?',
                'a' => 'The minimum deposit required to start trading on MoonExe is 100 USDT.',
            ],
            [
                'q' => 'Is there a minimum amount for withdrawals?',
                'a' => 'There is no minimum withdrawal amount; you can withdraw any sum. However, all withdrawals are subject to network fees. Small withdrawals may be less cost-effective due to these charges.',
            ],
            [
                'q' => 'Why is my withdrawal taking so long?',
                'a' => 'Withdrawals are typically processed within 24 hours. Delays can occur due to security checks, network congestion, or issues with your receiving wallet/exchange.',
            ],
            [
                'q' => 'What networks do you support for USDT withdrawals?',
                'a' => 'We currently support <b>USDT-TRC20</b>. Always ensure your receiving wallet or exchange supports this network.',
            ],
            [
                'q' => 'Why is there a withdrawal fee?',
                'a' => 'Fees cover on-chain transaction costs and help maintain platform security and services.',
            ],

            // ===== New: Trading & Orders =====
            [
                'q' => 'What are your trading hours?',
                'a' => 'All gates are open 24/7.',
            ],
            [
                'q' => 'Why was my order not executed?',
                'a' => 'Orders may not execute if there’s insufficient liquidity, high market volatility, or if a specific gate is temporarily closed.',
            ],
            [
                'q' => 'How are profits calculated and distributed?',
                'a' => 'Profits are based on exchange spreads and shared with liquidity providers. They are credited after order completion.',
            ],

            // ===== New: Technical Issues =====
            [
                'q' => 'The platform is slow or unresponsive. What should I do?',
                'a' => 'Try refreshing the page, clearing your browser cache, or switching browsers. If the issue persists, contact support and include a screenshot.',
            ],
            [
                'q' => 'Why can’t I see certain currency pairs?',
                'a' => 'Some pairs may be temporarily unavailable due to maintenance or low liquidity.',
            ],

            // ===== New: Referrals & Rewards =====
            [
                'q' => 'How do I refer a friend?',
                'a' => 'Share your unique referral link from the Referral section of your account. You’ll earn a commission on their activity.',
            ],
            [
                'q' => 'When are referral rewards paid?',
                'a' => 'Rewards are credited automatically after your referral completes a qualifying transaction.',
            ],
            [
                'q' => 'Why didn’t I receive my referral bonus?',
                'a' => 'This may occur due to delayed processing or if your referral didn’t meet the terms. Contact support with details for assistance.',
            ],

            // ===== New: Fees & Charges =====
            [
                'q' => 'What are the fees for withdrawing from my USDT Wallet (Flexible)?',
                'a' => 'Withdrawals from your flexible USDT wallet incur a fee of <b>7 USDT or 3%</b> of the transaction amount, whichever is higher.',
            ],
            [
                'q' => 'What are the rules for withdrawing my Trading Margin?',
                'a' => 'Trading Margin is designed for trading and can only be withdrawn upon full account termination. Early termination fees apply based on how long the margin has been held:<br><br>
                       <ul class="list-disc ml-5">
                         <li>Held for ≤100 days: 20% fee</li>
                         <li>Held for 101–200 days: 10% fee</li>
                         <li>Held for 200+ days: 0% fee</li>
                       </ul>',
            ],

            // ===== New: Compliance & Support =====
            [
                'q' => 'Is MoonExe regulated?',
                'a' => 'Yes, MoonExe operates as a compliant financial services provider and holds a <b>Money Services Business (MSB)</b> license. This foundational standard is used by major global financial networks (e.g., Visa, Mastercard, PayPal). Our secure infrastructure enables us to serve users in 100+ countries.',
            ],
            [
                'q' => 'How can I contact support?',
                'a' => 'Email us at <a href="mailto:support@moonexe.com">support@moonexe.com</a>.',
            ],
        ];

        return view('user.faq', [
            'title' => 'FAQ',
            'faqs'  => $faqs,
        ]);
    }
}
