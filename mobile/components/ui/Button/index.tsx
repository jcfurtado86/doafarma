import { Pressable, PressableProps, Text } from 'react-native';
import { a11y } from '@/utils/accessibility';
import { useNetworkStore } from '@/stores/networkStore';
import {
  baseStyles,
  sizeStyles,
  variantStyles,
  type ButtonSize,
  type ButtonVariant,
} from './styles';

export type { ButtonSize, ButtonVariant };

export interface ButtonProps extends Omit<PressableProps, 'style' | 'children'> {
  label: string;
  variant?: ButtonVariant;
  size?: ButtonSize;
  disableWhenOffline?: boolean;
}

export function Button({
  label,
  variant = 'primary',
  size = 'lg',
  disabled,
  disableWhenOffline = false,
  ...props
}: ButtonProps) {
  const isConnected = useNetworkStore((state) => state.isConnected);
  const isDisabled = disabled || (disableWhenOffline && !isConnected);

  const variantStyle = variantStyles[variant];
  const sizeStyle = sizeStyles[size];

  return (
    <Pressable
      {...a11y.button(label, isDisabled ?? undefined)}
      disabled={isDisabled}
      {...props}
      style={({ pressed }) => [
        baseStyles.container,
        sizeStyle.container,
        variantStyle.container,
        pressed && variantStyle.containerPressed,
        isDisabled && baseStyles.disabled,
      ]}
    >
      {({ pressed }) => (
        <Text
          style={[
            baseStyles.text,
            sizeStyle.text,
            variantStyle.text,
            pressed && variantStyle.textPressed,
          ]}
        >
          {label}
        </Text>
      )}
    </Pressable>
  );
}
