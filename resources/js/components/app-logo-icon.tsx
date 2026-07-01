import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            {...props}
            src="/svgeducart.svg"
            alt={props.alt ?? 'EduCart'}
            draggable={false}
        />
    );
}
