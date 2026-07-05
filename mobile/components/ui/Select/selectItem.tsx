import { Picker } from '@react-native-picker/picker';

interface SelectItemProps {
  label: string;
  value: string;
}

export function SelectItem({ label, value }: SelectItemProps) {
  return <Picker.Item label={label} value={value} />;
}
