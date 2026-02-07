import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { useSettings } from './useSettings';

export function useOnboarding() {
    const { getSetting, updateSetting } = useSettings();

    const startTour = () => {
        const driverObj = driver({
            showProgress: true,
            animate: true,
            nextBtnText: 'შემდეგი',
            prevBtnText: 'წინა',
            doneBtnText: 'დასრულება',
            onDestroyStarted: () => {
                driverObj.destroy();
                updateSetting('onboarding.completed', 'true').catch(() => {});
            },
            steps: [
                {
                    popover: {
                        title: 'მოგესალმებით!',
                        description: 'Ticker — თქვენი პირადი პროდუქტიულობის ინსტრუმენტი. მოდით გავეცნოთ ძირითად ფუნქციებს.',
                    },
                },
                {
                    element: '[data-tour="sidebar"]',
                    popover: {
                        title: 'ნავიგაცია',
                        description: 'გვერდითი პანელიდან შეგიძლიათ დღეებს შორის გადაადგილება. ← და → ღილაკებით ან კლავიატურის ისრებით.',
                    },
                },
                {
                    element: '[data-tour="sidebar-actions"]',
                    popover: {
                        title: 'მოქმედებები',
                        description: 'აქედან შექმნით ახალ დავალებებს (A), დროის ჩანაწერებს (S), განმეორებად ამოცანებს (R) და კატეგორიებს (K).',
                    },
                },
                {
                    element: '[data-tour="header"]',
                    popover: {
                        title: 'თარიღი',
                        description: 'აქ ნაჩვენებია მიმდინარე თარიღი. T ღილაკით დაუბრუნდებით დღეს.',
                    },
                },
                {
                    element: '[data-tour="todo-list"]',
                    popover: {
                        title: 'დავალებები',
                        description: 'მარცხენა პანელში მართავთ დავალებებს. შეცვალეთ სტატუსი, პრიორიტეტი და აღწერა.',
                    },
                },
                {
                    element: '[data-tour="todo-list"]',
                    popover: {
                        title: 'სტატუსები',
                        description: 'დავალებებს აქვთ 4 სტატუსი: backlog, todo, მიმდინარე და დასრულებული. ორჯერ დააწკაპუნეთ ტექსტზე რედაქტირებისთვის.',
                    },
                },
                {
                    element: '[data-tour="time-log-list"]',
                    popover: {
                        title: 'დროის აღრიცხვა',
                        description: 'მარჯვენა პანელში აღრიცხავთ სამუშაო დროს. კატეგორიები ავტომატურად მიენიჭება საკვანძო სიტყვებით.',
                    },
                },
                {
                    element: '[data-tour="time-log-progress"]',
                    popover: {
                        title: 'სამუშაო დღის პროგრესი',
                        description: 'პროგრესის ზოლი აჩვენებს 8-საათიანი სამუშაო დღის მიმდინარეობას.',
                    },
                },
                {
                    popover: {
                        title: 'კლავიატურის მალსახმობები',
                        description: 'A — ახალი დავალება, S — ახალი ჩანაწერი, T — დღეს, W — კვირის ხედი, ←/→ — დღეების ცვლა, D — ბნელი თემა',
                    },
                },
                {
                    element: '[data-tour="nav-pages"]',
                    popover: {
                        title: 'კვირის დაგეგმვა',
                        description: 'კვირის ხედში დაგეგმეთ დავალებები დღეების მიხედვით. W ღილაკით გადახვალთ.',
                    },
                },
                {
                    element: '[data-tour="settings-btn"]',
                    popover: {
                        title: 'პარამეტრები',
                        description: 'აქ შეცვლით პროფილს, თემას, დროის ზონას და ინტეგრაციებს.',
                    },
                },
                {
                    popover: {
                        title: 'მზად ხართ!',
                        description: 'წარმატებებს გისურვებთ! თუ ტურის თავიდან ნახვა გსურთ, პარამეტრებში იპოვით.',
                    },
                },
            ],
        });

        driverObj.drive();
    };

    const checkAndStartTour = () => {
        const completed = getSetting('onboarding.completed');
        if (completed.value !== 'true') {
            setTimeout(() => startTour(), 500);
        }
    };

    return {
        startTour,
        checkAndStartTour,
    };
}
