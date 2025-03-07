import { Pressable, StyleSheet, Text, PressableProps } from 'react-native';

interface PrimaryButtonProps extends PressableProps {
  label: string;
}

export default function PrimaryButton({ label, ...props }: PrimaryButtonProps) {
  return (
    <Pressable
      {...props}
      style={({ pressed }) => [styles.buttonPrimary, pressed && styles.buttonHover]}
    >
      <Text style={styles.buttonText}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  buttonPrimary: {
    backgroundColor: '#A4C457',
    paddingHorizontal: 16,
    borderRadius: 10,
    marginBottom: 16,
    height: 56,
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },

  buttonHover: {
    backgroundColor: '#8FA743',
  },

  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
    textAlign: 'center',
    lineHeight: 56,
  },
});
