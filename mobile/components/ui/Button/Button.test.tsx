import { fireEvent, render } from '@testing-library/react-native';
import { useNetworkStore } from '@/stores/networkStore';
import { colors } from '@/theme/tokens';
import { Button } from './index';

describe('Button', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders label with button accessibility role', () => {
    const { getByRole, getByText } = render(<Button label="Entrar" onPress={() => {}} />);

    expect(getByRole('button')).toBeTruthy();
    expect(getByText('Entrar')).toBeTruthy();
  });

  it('calls onPress when enabled and pressed', () => {
    const onPress = jest.fn();
    const { getByRole } = render(<Button label="Enviar" onPress={onPress} />);

    fireEvent.press(getByRole('button'));

    expect(onPress).toHaveBeenCalledTimes(1);
  });

  it('applies primary variant background by default', () => {
    const { getByRole } = render(<Button label="Primary" onPress={() => {}} />);

    expect(getByRole('button')).toHaveStyle({ backgroundColor: colors.primary });
  });

  it('applies outline variant with transparent background and primary border', () => {
    const { getByRole } = render(<Button label="Outline" variant="outline" onPress={() => {}} />);

    expect(getByRole('button')).toHaveStyle({
      backgroundColor: 'transparent',
      borderWidth: 1,
      borderColor: colors.primary,
    });
  });

  it.each([
    ['sm', 40],
    ['md', 48],
    ['lg', 56],
  ] as const)('applies height %s → %ipx', (size, height) => {
    const { getByRole } = render(<Button label="Size" size={size} onPress={() => {}} />);

    expect(getByRole('button')).toHaveStyle({ height });
  });

  it('does not call onPress when disabled and exposes disabled a11y state', () => {
    const onPress = jest.fn();
    const { getByRole } = render(<Button label="Disabled" disabled onPress={onPress} />);

    const button = getByRole('button');
    fireEvent.press(button);

    expect(onPress).not.toHaveBeenCalled();
    expect(button.props.accessibilityState).toEqual({ disabled: true });
  });

  it('stays inert when disableWhenOffline and network is disconnected', () => {
    (useNetworkStore as unknown as jest.Mock).mockImplementationOnce(
      (selector: (state: { isConnected: boolean }) => unknown) => selector({ isConnected: false })
    );
    const onPress = jest.fn();
    const { getByRole } = render(<Button label="Offline" disableWhenOffline onPress={onPress} />);

    const button = getByRole('button');
    fireEvent.press(button);

    expect(onPress).not.toHaveBeenCalled();
    expect(button.props.accessibilityState).toEqual({ disabled: true });
  });

  it('remains pressable when disableWhenOffline but connected', () => {
    const onPress = jest.fn();
    const { getByRole } = render(<Button label="Online" disableWhenOffline onPress={onPress} />);

    fireEvent.press(getByRole('button'));

    expect(onPress).toHaveBeenCalledTimes(1);
  });
});
